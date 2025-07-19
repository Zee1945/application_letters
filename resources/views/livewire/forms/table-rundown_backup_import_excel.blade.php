<div>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Tanggal/Waktu</th>
                <th>Acara</th>
                <th>Narasumber & Moderator</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rundowns as $index => $row)
                <tr>
                    <td style="width: 200px">
                        <div class="">
                            <span>{{viewHelper::humanReadableDate($row['date'])}}</span>
                        </div>
                        <div class="d-flex mt-3">
                            <span>{{ viewHelper::getHourAndMinute($row['start_date'])}}</span>
                            <h5 class="mx-2">-</h5>
                            <span>{{ viewHelper::getHourAndMinute($row['end_date'])}}</span>

                        </div>

                    </td>

                    <td><span>{{$row['name']}}</span></td>

                    <td>
                        @if (!empty($row['speaker_text']))
                        <ul>
                            <li class="fw-bold"> Narasumber</li>
                            @forelse (explode(';',$row['speaker_text']) as $item)
                            <li>{{$item}}</li>
                            @empty
                            @endforelse


                        </ul>
                        @endif

                        <br>
                        <hr>
                        @if (!empty($row['moderator_text']))
                        <ul>
                            <li class="fw-bold"> Moderator</li>
                            @forelse (explode(';',$row['moderator_text']) as $item)
                            <li>{{$item}}</li>
                            @empty
                            @endforelse
                        </ul>
                        @endif



                    </td>

                </tr>
            @endforeach
        </tbody>
    </table>

</div>
