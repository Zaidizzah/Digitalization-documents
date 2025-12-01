<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Http\Response;
use Ilovepdf\PdfjpgTask;
use setasign\Fpdi\Fpdi;
use setasign\Fpdf\Fpdf;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class OcrService
{
    protected const OCRSPACE_MAX_PDF_PAGES_PROCESSED = 3;
    protected const OCRSPACE_MAX_FILE_SIZE = 1048576; // 1MB

    protected const ILOVEAPI_MAX_FILE_SIZE = 20971520; // 20MB
    protected const ILOVEAPI_MAX_PDF_PAGES_PROCESSED = 500;

    protected const WEBSERVICEOCR_MAX_FILE_SIZE = 20971520; // 20MB
    protected const WEBSERVICEOCR_MAX_PDF_PAGES_PROCESSED = 500;

    /**
     * Validates a file path and name before processing it to OCR.Space
     * 
     * @param string $file_path path to file
     * @param string $file_name name of file
     * @throws \InvalidArgumentException if file is missing, 
     *                                   if file does not exist, 
     *                                   if file is more than 1MB, 
     * @return void
     */
    private static function validate_process_file(string $file_path, string $file_name)
    {
        if (empty($file_path) || empty($file_name)) {
            throw new \InvalidArgumentException(
                'Cannot process file. Missing file path or file name.',
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!Storage::exists($file_path)) {
            throw new \InvalidArgumentException(
                "File $file_name does not exist",
                Response::HTTP_NOT_FOUND
            );
        }
    }

    /**
     * Upload and process file to OCR result
     * 
     * @param string $file_path path to file
     * @param string $file_name name of the file and its extension
     * @return array
     */
    public static function process_file(string $file_path, string $file_name)
    {
        // validate the process file
        self::validate_process_file($file_path, $file_name);

        $file_extension = pathinfo(Storage::path($file_path), PATHINFO_EXTENSION);
        $file_size = Storage::size($file_path);

        $is_pdf = $file_extension === 'pdf';
        if ($is_pdf) {
            $pdf_pages = self::get_pdf_pages($file_path, $file_name);
        }

        if ($file_size <= self::OCRSPACE_MAX_FILE_SIZE && (!$is_pdf || $pdf_pages <= self::OCRSPACE_MAX_PDF_PAGES_PROCESSED)) {
            return self::ocrspace_extract($file_path, $file_name);
        } else if ($file_size <= 5242880 && (!$is_pdf || $pdf_pages <= 10)) {
            return self::ocrspace_extract($file_path, $file_name, true);
        } else if ($file_size <= self::WEBSERVICEOCR_MAX_FILE_SIZE && (!$is_pdf || $pdf_pages <= self::WEBSERVICEOCR_MAX_PDF_PAGES_PROCESSED)) {
            try {
                // Ocr using OCRWEBSERVICE API, but limit has reached (caused \RuntimeException) then replace with TESSERACT OCR service
                return self::ocrwebservice_extract($file_path, $file_name);
            } catch (\RuntimeException $e) {
                try {
                    // Replace with Tesseract OCR service
                    $process = new Process([
                        'C:\Users\asusb\AppData\Local\Programs\Python\Python313\python.exe',
                        Storage::path('scripts/python/ocr_script.py'),
                        json_encode(["file_path" => Storage::path($file_path), "file_type" => pathinfo(Storage::path($file_path), PATHINFO_EXTENSION)]),
                    ]);
                    $process->run();

                    // Jika ada error dalam proses 
                    if (!$process->isSuccessful()) {
                        throw new ProcessFailedException($process);
                    }

                    $result = json_decode($process->getOutput(), true);
                    if (!empty($result['error'])) {
                        throw new \RuntimeException(Arr::join($result['error'], ', ', ', and '), Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                    return [
                        'text' => $result['text']
                    ];
                } catch (ProcessFailedException $e) {
                    throw new \InvalidArgumentException(
                        "File $file_name size is too large or too many pages, that cannot be processed and caused an error/timeout.",
                        Response::HTTP_INTERNAL_SERVER_ERROR,
                        $e
                    );
                }
            }
        } else {
            throw new \InvalidArgumentException(
                "File $file_name size is too large or too many pages, that cannot be processed by OCR system and caused an error/timeout.",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     *
     * @param string $file_path Path to the PDF file.
     * @param string $file_name Name of the PDF file.
     */
    private static function get_pdf_pages(string $file_path, string $file_name)
    {
        $file_path = Storage::path($file_path);

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($file_path);
        return $pageCount;
    }

    private static function ocrspace_extract(string $file_path, string $file_name, $use_spare_api_key = false)
    {
        $OCRSPACE_api_key = $use_spare_api_key ? env('OCRSPACE_API_KEY_SPARE') : env('OCRSPACE_API_KEY');

        if (empty($OCRSPACE_api_key)) {
            throw new \InvalidArgumentException(
                'Cannot process file. Missing some environment configuration variable.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $response = Http::attach(
            'file',
            Storage::get($file_path),
            $file_name
        )->withHeaders([
            'apiKey' => $OCRSPACE_api_key
        ])->post('https://api.ocr.space/parse/image?detectOrientation=true');

        $result = $response->json();

        if (!empty($result['IsErroredOnProcessing'])) {
            $message = Arr::join($result['ErrorMessage'], ", ") . ". Please try again.";

            throw new \RuntimeException($message, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (pathinfo(Storage::path($file_path), PATHINFO_EXTENSION) === 'pdf') {
            $text_result = collect($result['ParsedResults'])
                ->map(function ($result, $index) {
                    $page = $index + 1;

                    return "===== PAGE $page ======\n{$result['ParsedText']}\n\n";
                })
                ->implode("\n");
        } else {
            $text_result = collect($result['ParsedResults'])->pluck('ParsedText')->implode("\n");
        }

        return [
            'text' => $text_result
        ];
    }

    private static function ilovepdf_extract(string $file_path, string $file_name)
    {
        $iloveapi_public_key = env('ILOVEAPI_PUBLIC_KEY');
        $iloveapi_secret_key = env('ILOVEAPI_SECRET_KEY');

        if (empty($iloveapi_public_key) || empty($iloveapi_secret_key)) {
            throw new \InvalidArgumentException(
                'Cannot process file. Missing some environment configuration variable.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $my_task = new PdfjpgTask($iloveapi_public_key, $iloveapi_secret_key);

        $file1 = $my_task->addFile(Storage::path($file_path));

        $my_task->setMode('pages');
        $my_task->setOutputFilename($file_name);
        $my_task->setPackagedFilename($file_name);

        $my_task->execute();

        $resources = $my_task->downloadStream();
        $contents = $resources->getBody()->getContents();

        // Set file download path to 'documents/files/extracted_<timestamp>/<file_name>'
        Storage::disk('local')->put("documents/extracted/files_" . date('Y_m_d_His') . "/$file_name.zip", $contents);

        return [
            'text' => nl2br(htmlspecialchars(Storage::get("documents/extracted/files_" . date('Y_m_d_His') . "/$file_name.txt"), ENT_QUOTES, 'UTF-8'))
        ];
    }

    private static function ocrwebservice_extract(string $file_path, string $file_name)
    {
        $username = env('OCRWEBSERVICE_USERNAME');
        $license_code = env('OCRWEBSERVICE_LICENSE_CODE');

        if (empty($username) || empty($license_code)) {
            throw new \InvalidArgumentException(
                'Cannot process file. Missing some environment configuration variable.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $response = Http::withBasicAuth($username, $license_code)
            ->attach('file', Storage::get($file_path), $file_name)
            ->post('http://www.ocrwebservice.com/restservices/processDocument?gettext=true&newline=1&pagerange=allpages', [
                'language' => 'english',
                'outputformat' => 'txt',
            ]);

        $response = $response->json();

        if ($response->failed()) {
            throw new \RuntimeException("OCR processing failed: {$response->body()}", $response->status());
        }

        if ($response['ErrorMessage'] !== '') {
            throw new \RuntimeException("OCR processing failed: {$response['ErrorMessage']}");
        }

        $text_output = '';
        foreach ($response['OCRText'] as $files_ocr_result) {
            $page_number = 1;
            foreach ($files_ocr_result as $page) {
                $text_output .= "===== PAGE $page_number ======\n{$page['text']}\n\n";
            }
        }

        return [
            'text' => $text_output
        ];
    }

    private static function adobeocr_extract(string $file_path, string $file_name)
    {
        $adobe_api_key = env('ADOBE_API_KEY');
        $adobe_client_id = env('ADOBE_CLIENT_ID_KEY');
        $adobe_secret_key = env('ADOBE_SECRET_KEY');
        $endpoint_base_url = 'https://pdf-services.adobe.io/';

        if (empty($adobe_api_key) || empty($adobe_client_id) || empty($adobe_secret_key)) {
            throw new \InvalidArgumentException(
                'Cannot process file. Missing some environment configuration variable.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        function get_access_Token($adobe_client_id, $adobe_secret_key)
        {
            $response = Http::asForm()->post("https://pdf-services-ue1.adobe.io/token", [
                'client_id' => $adobe_client_id,
                'client_secret' => $adobe_secret_key,
            ]);

            if ($response->failed()) {
                throw new \Exception("Access token request failed: {$response->body()}", $response->status());
            }

            return $response->json()['access_token'];
        }

        function upload_file_to_adobe($adobe_client_id, $file_path, $file_name, $access_token)
        {
            $response = Http::withHeaders([
                'Authorization' => "Bearer $access_token",
                'x-api-key' => $adobe_client_id,
                'Content-Type' => 'application/json',
            ])->post("https://pdf-services-ue1.adobe.io/assets", [
                'mediaType' => Storage::mimeType($file_path)
            ]);

            $upload_uri = $response->json()['uploadUri'];
            $asset_id = $response->json()['assetID'];

            // Upload the file
            $uploaded_response = Http::withHeader('Content-Type', Storage::mimeType($file_path))->put($upload_uri, Storage::path($file_path));

            if ($uploaded_response->failed()) {
                throw new \Exception("File upload failed: {$response->body()}", $response->status());
            }

            return $asset_id;
        }

        function start_ocr_process($adobe_client_id, $asset_id, $access_token)
        {
            $response = Http::withHeaders([
                'Authorization' => "Bearer $access_token",
                'x-api-key' => $adobe_client_id,
                'Content-Type' => 'application/json',
            ])->post("https://pdf-services-ue1.adobe.io/operation/ocr", [
                'assetID' => $asset_id,
                'ocrLang' => 'en-US',
                'ocrType' => 'searchable_image'
            ]);

            if ($response->failed()) {
                throw new \RuntimeException("OCR processing failed: {$response->body()}", $response->status());
            }

            // get JOB ID or request id in header
            return $response->header('x-request-id');
        }

        function check_ocr_progress_status($adobe_client_id, $job_id, $access_token)
        {
            $response = Http::withHeaders([
                'Authorization' => "Bearer $access_token",
                'x-api-key' => $adobe_client_id,
                'Content-Type' => 'application/json',
            ])->get("https://pdf-services-ue1.adobe.io/operation/ocr/$job_id/status");

            if ($response->failed()) {
                throw new \RuntimeException("Failed to check OCR status: {$response->body()}", $response->status());
            }

            return $response->json();
        }

        $access_token = get_access_Token($adobe_client_id, $adobe_secret_key);
        $asset_id = upload_file_to_adobe($adobe_client_id, $file_path, $file_name, $access_token);
        $job_id = start_ocr_process($adobe_client_id, $asset_id, $access_token);

        $status = check_ocr_progress_status($adobe_client_id, $job_id, $access_token);
        while ($status['status'] !== 'done' && $status['status'] !== 'failed') {
            sleep(5);
            $status = check_ocr_progress_status($adobe_client_id, $job_id, $access_token);
        }

        dd('DOCUMENT CORRUPTED:issue');
    }
}
