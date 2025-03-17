<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Export</title>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th align="center" bgcolor="#c9c9c9" style="border: solid 1px; border-collapse: collapse; font-weight: bold;">No</th>
                @foreach ($headings as $name)
                    <th align="center" bgcolor="#c9c9c9" style="border: solid 1px; border-collapse: collapse; font-weight: bold;">{{ $name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $no => $row)
                <tr>
                    <td align="center" style="border: solid 1px; border-collapse: collapse;">{{ $loop->iteration }}</td>
                    @foreach ($row as $col)
                        <td align="center" style="border: solid 1px; border-collapse: collapse;">{{ $col }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>