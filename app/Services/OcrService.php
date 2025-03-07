<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Http\Response;
use Ilovepdf\Ilovepdf;
use Ilovepdf\PdfocrTask;
use setasign\Fpdi\Fpdi;
use setasign\Fpdf\Fpdf;

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
     *                                   if file is a PDF with more than 3 pages
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
     * Upload and process file to OCR.Space
     * 
     * @param string $file_path path to file
     * @param string $file_name name of file
     * @return array
     */
    public static function process_file(string $file_path, string $file_name)
    {
        // validate the process file
        self::validate_process_file($file_path, $file_name);

        switch (pathinfo($file_path, PATHINFO_EXTENSION)) {
            case 'pdf':
                $pdf_pages = self::get_pdf_pages($file_path, $file_name);

                return self::ocrwebservice_extract($file_path, $file_name);
                if (Storage::size($file_path) <= self::OCRSPACE_MAX_FILE_SIZE && $pdf_pages <= self::OCRSPACE_MAX_PDF_PAGES_PROCESSED) {
                    return self::ocrspace_extract($file_path, $file_name);
                } else if (Storage::size($file_path) <= self::ILOVEAPI_MAX_FILE_SIZE && $pdf_pages <= self::ILOVEAPI_MAX_PDF_PAGES_PROCESSED) {
                    return self::ilovepdf_extract($file_path, $file_name);
                } else {
                }

                break;

            default:
                # code...
                break;
        }
    }

    /**
     * Get the number of pages in a PDF file.
     *
     * @param string $file_path Path to the PDF file.
     * @param string $file_name Name of the PDF file.
     * @return int Number of pages in the PDF.
     */
    private static function get_pdf_pages(string $file_path, string $file_name)
    {
        $file_path = Storage::path($file_path);

        if (!file_exists($file_path)) {
            throw new \InvalidArgumentException(
                "File $file_name does not exist",
                Response::HTTP_NOT_FOUND
            );
        }

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($file_path);
        return $pageCount;
    }

    private static function ocrspace_extract(string $file_path, string $file_name)
    {
        $OCRSPACE_api_key = env('OCRSPACE_API_KEY');

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

        return [
            'text' => collect($result['ParsedResults'])->pluck('ParsedText')->implode("\n")
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

        // This dependency is motherf*ck*r broken, don't try anything to fix it
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
            ->post('http://www.ocrwebservice.com/restservices/processDocument?gettext=true', [
                'language' => 'english',
                'outputformat' => 'txt',
            ]);

        if ($response->failed()) {
            throw new \Exception('OCR API Error: ' . $response->body());
        }

        dd($response->body());

        return $response->body();
    }
}
