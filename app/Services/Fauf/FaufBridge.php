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
            $contents['type'] = $generic['response_type'];
            switch ($contents['type']) {
                case "option":
                    $options = array();
                    foreach ($generic['options'] as $opt) {
                        $options[$opt['label']] = $opt['value']['input']['text'];
                    }
                    $contents['body'] = $options;
                    break;
                case "text":
                    $contents['body'] = $responseArray['output']['text'][0];
                    break;
            }
            array_push($result, $contents);
        }

        return $result;
    }
}
