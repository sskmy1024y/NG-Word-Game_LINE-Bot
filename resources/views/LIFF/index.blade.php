@extends('layouts.liff')

@section('content')
<div class="card">
    <h5 id="myName" class="card-header">あなたの名前</h5>
    <div class="card-body">
        <h5 class="card-title">へのへのさんのお題</h5>
        <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
        <select class="custom-select custom-select-lg mb-3">
            <option selected>Open this select menu</option>
            <option value="1">One</option>
            <option value="2">Two</option>
            <option value="3">Three</option>
        </select>
        <button type="button" class="btn btn-primary btn-lg">決定</button>
    </div>
</div>
@endsection


@section('script')
<script>
    $(function() {
        liff.init(data => {
            if (data.context.type == "none") alert("アプリ以外からは開けません");
            if (data.context.userId) {
                liff.getProfile().then(profile => {
                    $("#myName").html(profile.displayName)
                    fetch(`https://${document.domain}/api/liff_api`, {
                        method: 'POST',
                        body: JSON.stringify({
                            method: 'getDecider',
                            sessionID: '{{ $gameSessionID }}',
                            sourceID: getEventSourceId(data.context),
                            userID: data.context.userId
                        }), // 文字列で指定する
                        headers: {
                            "Content-Type": "application/json; charset=utf-8",
                        },
                        cache: "no-cache",
                        mode: 'cors'
                    }).then(response => {
                        return response.json();
                    }, err => alert(err)).then(data => {
                        alert(JSON.stringify(data));
                    })
                })
            }
        }, error => {
            alert("不明なエラー")
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