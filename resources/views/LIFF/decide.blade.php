@extends('layouts.liff')

@section('content')
<div class="card">
    <h5 id="myName" class="card-header">あなたの名前</h5>
    <div id="changable" class="card-body" style="display:none">
        <h5 class="card-title"><span id="decide-user"></span>さんのお題を選んでね</h5>
        <p class="card-text">相手が言ってはいけないワードを選ぼう</p>
        <select id="selected-word" class="custom-select custom-select-lg mb-3">
        </select>
        <button type="button" id="submitButton" class="btn btn-primary btn-lg">決定</button>
    </div>
    <div id="nochange" class="card-body" style="display:none">
        <h5 class="card-title">もう変えられないよ</h5>
        <button type="button" id="submitButton" class="btn btn-primary btn-lg">閉じる</button>
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
                    fetch(`https://${document.domain}${location.pathname}/../api/liff_api`, {
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
                    }, err => alert(err)).then(res => {
                        $('#loading').hide(); // ローディングを消す
                        if (!res.success) { // 変更できない
                            $('#nochange').show();
                            return false;
                        }
                        $('#changable').show();
                        $('#decide-user').html(res.decide_user_name)
                        res.candidacy_keywords.forEach(keyword => {
                            $('#selected-word').append($('<option>').html(
                                keyword.value).val(keyword.key));
                        });
                        $('#submitButton').click(() => {
                            submitKeyword(data)
                        });

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

    function submitKeyword(liffData) {
        const decideUserName = $('#decide-user').text();
        const keyword_id = $('#selected-word').val();
        fetch(`https://${document.domain}/api/liff_api`, {
            method: 'POST',
            body: JSON.stringify({
                method: 'selectedWord',
                sessionID: '{{ $gameSessionID }}',
                sourceID: getEventSourceId(liffData.context),
                userID: liffData.context.userId,
                keywordID: keyword_id
            }), // 文字列で指定する
            headers: {
                "Content-Type": "application/json; charset=utf-8",
            },
            cache: "no-cache",
            mode: 'cors'
        }).then(response => {
            return response.json();
        }, err => alert(err)).then(data => {
            if (!data.success) liff.closeWindow();
            else {
                liff.sendMessages([{
                        type: 'text',
                        text: decideUserName + "さんのワードを決めました！"
                    }])
                    .then(() => {
                        liff.closeWindow()
                    })
                    .catch((err) => {
                        alert("不明なエラーが発生しました")
                    });
            }
        })
    }
</script>
@endsection

@section('style')
<style>
    #myName {}
</style>
@endsection