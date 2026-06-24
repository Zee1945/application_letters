<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Rincian Anggaran Biaya</title>
</head>
<body>
{{--
    PENTING: Urutan & jumlah kolom HARUS sama dengan yang dibaca DraftCostImport:
    1 baris header, kolom A=Kode, B=Uraian, C=Sub Uraian, D=Volume, E=Satuan, F=Harga Satuan.
    Jangan tambah kolom "No" atau "Total" — importer tidak membacanya & akan menggeser indeks.
--}}
<table>
    <thead>
        <tr>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Kode</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Uraian</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Sub Uraian</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Volume</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Satuan</th>
            <th style="border: 3px solid black; font-weight:bold; text-align:center; background-color:#156082;color:white">Harga Satuan</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($draft_costs as $row)
            <tr>
                <td style="border: 3px solid black;">{{ $row['code'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $row['item'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $row['sub_item'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $row['volume'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $row['unit'] ?? '' }}</td>
                <td style="border: 3px solid black;">{{ $row['cost_per_unit'] ?? '' }}</td>
            </tr>
        @empty
        @endforelse
    </tbody>
</table>
</body>
</html>
