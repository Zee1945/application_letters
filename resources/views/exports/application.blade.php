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
            <th colspan="4" style="font-weight: bold; text-align: center; font-size: 15rem;">Pilih Narasumber dan Moderator</th>
            <th></th>
            <th></th>
            <th colspan="3" style="font-weight: bold; text-align: center; font-size: 15rem;">Pilih Panitia</th>
            <th></th>
            <th></th>
            <th colspan="3" style="font-weight: bold; text-align: center; font-size: 15rem;">Pilih Peserta</th>
            <th></th>
            <th></th>
            <th colspan="8" style="font-weight: bold; text-align: center; font-size: 15rem;"><h5>Susun Rencana Anggaran Biaya</h5></th>
        </tr>
        <tr>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">No</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Nama Narasumber</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Institusi Narasumber</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Peran</th>
            <th></th>
            <th></th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">No</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Posisi Panitia</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Nama Panitia</th>
            <th></th>
            <th></th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">No</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Nama Peserta</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Institusi Peserta</th>
            <th></th>
            <th></th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">No</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Kode</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Item</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Sub Item</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Volume</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Satuan</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Harga per Unit</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Total</th>
        </tr>
    </thead>
    <tbody>
        @php
            $maxRows = max(
                count($speaker_moderator),
                count($commitee),
                count($participants),
                count($draft_costs),
            );
        @endphp

        @for ($i = 0; $i < $maxRows; $i++)
            <tr>
                {{-- Narasumber dan Moderator --}}
                <td style="border: 3px solid black;">{{ $i + 1 }}</td>
                <td style="border: 3px solid black;">{{ $speaker_moderator[$i]['name'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $speaker_moderator[$i]['institution'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $speaker_moderator[$i]['participant_type_name'] ?? '' }}</td>
                <td></td>
                <td></td>

                {{-- Panitia --}}
                <td style="border: 3px solid black;">{{ $i + 1 }}</td>
                <td style="border: 3px solid black;">{{ $commitee[$i]['commitee_position'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $commitee[$i]['name'] ?? '' }}</td>
                <td></td>
                <td></td>

                {{-- Peserta --}}
                <td style="border: 3px solid black;">{{ $i + 1 }}</td>
                <td style="border: 3px solid black;">{{ $participants[$i]['name'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $participants[$i]['institution'] ?? '' }}</td>
                <td></td>
                <td></td>

                {{-- RAB --}}
                <td style="border: 3px solid black;">{{ $i + 1 }}</td>
                <td style="border: 3px solid black;">{{ $draft_costs[$i]['code'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $draft_costs[$i]['item'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $draft_costs[$i]['sub_item'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $draft_costs[$i]['volume'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $draft_costs[$i]['unit'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $draft_costs[$i]['cost_per_unit'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $draft_costs[$i]['total'] ?? '' }}</td>
            </tr>
        @endfor
    </tbody>
</table>
</body>
</html>