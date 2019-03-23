<?php
namespace App\Services\Fauf;

use App\Services\Watson\CallWatsonAssistant;

class FaufBridge
{
    /**
     * 各種UIとWatsonを繋ぐBridge Service
     *
     * @param array $request リクエスト配列
     * @return json Watsonからの返答をjsonで返す。
     */
    public function callAI(array $request)
    {
        $CWA = new CallWatsonAssistant();
        $response = $CWA->call($request['text'], session('context') ? session('context'):[]);
        $responseArray = json_decode($response, true);

        $result = array();
        for ($i=0; $i < count($responseArray['output']['generic']); $i++) {
            $generic = $responseArray['output']['generic'][$i];
            switch ($generic['response_type']) {
                case "option":
                    array_merge($result, $generic);
                    break;
                case "text":
                    $text = $responseArray['output']['text'][0];
                    str_replace('USERNAME', 'sho', $text);
                    array_push($result, $text);
                    break;
            }
        }
        return $result;

        // for ($i=0; $i < count($responseArray['output']['generic']); $i) {
        //     if (!empty($responseArray['output']['text'][$i])) {
        //         return $responseArray['output']['text'][$i];
        //     } elseif (is_array($responseArray['output']['generic'][$i])) {
        //         return json_encode($responseArray['output']['generic'][$i]);
        //     }
        // }
    }
}
