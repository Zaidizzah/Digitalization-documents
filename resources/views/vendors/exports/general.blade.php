<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Export data</title>
</head>
<body>
    <table>
        @if ($action !== 'example')
            <thead>
                <tr>
                    <th align="center" bgcolor="#c9c9c9" style="border: solid 1px; border-collapse: collapse; font-weight: bold;">No</th>
                    @foreach ($headings as $name)
                        <th align="center" bgcolor="#c9c9c9" style="border: solid 1px; border-collapse: collapse; font-weight: bold;">{{ $name }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            @foreach ($data as $no => $row)
                <tr>
                    @if ($action !== 'example')
                        <td align="center" style="border: solid 1px; border-collapse: collapse;">{{ $loop->iteration }}</td>
                    @endif
                    @foreach ($row as $col)
                        <td align="center" style="border: solid 1px; border-collapse: collapse;">{{ $col }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>