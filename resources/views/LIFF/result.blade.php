@extends('layouts.liff')

@section('content')
<div class="card">
    <h5 id="myName" class="card-header">結果発表</h5>
    <div class="card-body">
        <h5 class="card-title">みんなのお題</h5>
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">名前</th>
                    <th scope="col">お題</th>
                </tr>
            </thead>
            <tbody id="tablebody">

            </tbody>
        </table>
        <button type="button" id="closeButton" class="btn btn-primary btn-lg btn-block">閉じる</button>
    </div>
</div>
@endsection


@section('script')
<script>
    $(function() {
        liff.init(data => {
            if (data.context.type == "none") alert("アプリ以外からは開けません");
            fetch(`https://${document.domain}/api/liff_api`, {
                method: 'POST',
                body: JSON.stringify({
                    method: 'result',
                    sessionID: '{{ $gameSessionID }}',
                    sourceID: getEventSourceId(data.context)
                }), // 文字列で指定する
                headers: {
                    "Content-Type": "application/json; charset=utf-8",
                },
                cache: "no-cache",
                mode: 'cors'
            }).then(response => {
                return response.json();
            }, err => alert(err)).then(res => {
                $('#loading').hide(); // ローディングを消す
                res.users.forEach(user => {
                    $('#tablebody').append($(
                        `<tr><td>${user.name}</td><td>${user.keyword}</td></tr>`
                    ));
                });
            })
        }, error => {
            alert("不明なエラー")
        })

        $('#closeButton').click(() => {
            liff.closeWindow()
        })
    });

    function getEventSourceId(context) {
        if (context.type == 'group') return context.groupId;
        else if (context.type == 'room') return context.roomId
        else if (context.type == 'utou') return context.utouId
        else return null
    }
</script>
@endsection

@section('style')
<style>
    #myName {}
</style>
@endsection