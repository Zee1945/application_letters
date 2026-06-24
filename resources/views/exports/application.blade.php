<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
     <style>
        /* table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 3px solid black;
            padding: 8px;
            text-align: left;
        } */
        /* th {
            background-color: #f2f2f2;
        } */
    </style>
</head>
<body>
<table>
    <thead>
        <tr>
            <th colspan="7" style="font-weight: bold; text-align: center; font-size: 15rem;">Pilih Narasumber dan Moderator</th>
            <th></th>
            <th></th>
            <th colspan="7" style="font-weight: bold; text-align: center; font-size: 15rem;">Pilih Panitia</th>
            <th></th>
            <th></th>
            <th colspan="6" style="font-weight: bold; text-align: center; font-size: 15rem;">Pilih Peserta</th>
        </tr>
        <tr>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">No</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Nama</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">NIP</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Pangkat (Golongan)</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Jabatan Fungsional</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Jabatan-Lembaga</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Peran</th>
            <th></th>
            <th></th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">No</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Peran Kepanitiaan</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Nama</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">NIP</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Pangkat (Golongan)</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Jabatan Fungsional</th>
            <th></th>
            <th></th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">No</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Nama</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">NIP</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Pangkat (Golongan)</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Jabatan Fungsional</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Jabatan-Lembaga</th>
        </tr>
    </thead>
    <tbody>
        @php
            $maxRows = max(
                count($speaker_moderator),
                count($commitee),
                count($participants),
            );
        @endphp

        @for ($i = 0; $i < $maxRows; $i++)
            <tr>
                {{-- Narasumber dan Moderator --}}
                <td style="border: 3px solid black;">{{ $i + 1 }}</td>
                <td style="border: 3px solid black;">{{ $speaker_moderator[$i]['name'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $speaker_moderator[$i]['nip'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $speaker_moderator[$i]['rank'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $speaker_moderator[$i]['functional_position'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $speaker_moderator[$i]['institution'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $speaker_moderator[$i]['participant_type_name'] ?? '' }}</td>
                <td></td>
                <td></td>

                {{-- Panitia --}}
                <td style="border: 3px solid black;">{{ $i + 1 }}</td>
                <td style="border: 3px solid black;">{{ $commitee[$i]['commitee_position'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $commitee[$i]['name'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $commitee[$i]['nip'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $commitee[$i]['rank'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $commitee[$i]['functional_position'] ?? '' }}</td>
                <td></td>
                <td></td>

                {{-- Peserta --}}
                <td style="border: 3px solid black;">{{ $i + 1 }}</td>
                <td style="border: 3px solid black;">{{ $participants[$i]['name'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $participants[$i]['nip'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $participants[$i]['rank'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $participants[$i]['functional_position'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $participants[$i]['institution'] ?? '' }}</td>
            </tr>
        @endfor
    </tbody>
</table>
</body>
</html>