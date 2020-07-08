<?php
//   Copyright 2019 NEC Corporation
//
//   Licensed under the Apache License, Version 2.0 (the "License");
//   you may not use this file except in compliance with the License.
//   You may obtain a copy of the License at
//
//       http://www.apache.org/licenses/LICENSE-2.0
//
//   Unless required by applicable law or agreed to in writing, software
//   distributed under the License is distributed on an "AS IS" BASIS,
//   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//   See the License for the specific language governing permissions and
//   limitations under the License.
//
    //////////////////////////////////////////////////////////////////////
    //
    //  【概要】
    //    Terraformドライバ RESTAPI接続関数
    //
    //////////////////////////////////////////////////////////////////////
    function terraform_restapi_access(  $hostname,
                                        $token,
                                        $requestURI,
                                        $method,
                                        $requestContents 
                                    ){
        ///////////////////////////
        // 返却用のArrayを定義      //
        ///////////////////////////
        $respons_array = array();

        ///////////////////////////
        // パラメータチェック           //
        ///////////////////////////

        $check_err_flag = 0;

        if( empty( $hostname ) ) // ホスト名が空
        {
            $respons_array['StatusCode']      = ( int ) -1;
            $respons_array['ResponsContents'] = array( "ErrorMessage" => "hostname is empty" );
            $check_err_flag = 1;
        }
        else if( empty( $token ) )   // トークンが空
        {
            $respons_array['StatusCode']      = ( int ) -1;
            $respons_array['ResponsContents'] = array( "ErrorMessage" => "Token is empty" );
            $check_err_flag = 1;
        }
        else if( empty( $requestURI ) )      // 呼び先のRESTAPIのホスト部以下のURIが空
        {
            $respons_array['StatusCode']      = ( int ) -1;
            $respons_array['ResponsContents'] = array( "ErrorMessage" => "requestURI is empty" );
            $check_err_flag = 1;
        }
        else if( empty( $method ) )          // リクエストメソッド(POST or GET)が空
        {
            $respons_array['StatusCode']      = ( int ) -1;
            $respons_array['ResponsContents'] = array( "ErrorMessage" => "method is empty" );
            $check_err_flag = 1;
        }
        /* GETの場合はnullが入るのでチェックしない
        else if( !is_array( $requestContents ) ) // RESTAPIに渡すTerraform実行用パラメータが空
        {
            $respons_array['StatusCode']      = ( int ) -1;
            $respons_array['ResponsContents'] = array( "ErrorMessage" => "requestContents is not array" );
            $check_err_flag = 1;
        }
        */

        if( $check_err_flag == 0 ){
            ////////////////////////////////
            // RequestHeader作成          //
            ////////////////////////////////
            $Header = array( "Authorization: Bearer ". $token,
                             "Content-Type: application/vnd.api+json");

            ////////////////////////////////
            // HTTPコンテキスト作成       //
            ////////////////////////////////
            if($requestContents == null){
                $HttpContext = array( "http" => array( "method"        => $method,
                                                       "header"        => implode("\r\n", $Header),
                                                       "ignore_errors" => true));
            }else{
                $HttpContext = array( "http" => array( "method"        => $method,
                                                       "header"        => implode("\r\n", $Header),
                                                       "content"       => json_encode($requestContents, JSON_UNESCAPED_UNICODE),
                                                       "ignore_errors" => true));
            }

            //=====================================================
            // SSLサーバ証明書の検証を無効化
            //=====================================================
            $HttpContext['ssl']['verify_peer']=false;
            $HttpContext['ssl']['verify_peer_name']=false;

            ////////////////////////////////
            // REST APIアクセス           //
            ////////////////////////////////
            $http_response_header = null;
            $ResponsContents = file_get_contents( "https://" . $hostname . "/" . $requestURI,
                false,
                stream_context_create($HttpContext) );

            //----------------------------------------------------------------------
            //出力のバッファリングをオンに設定
            //----------------------------------------------------------------------
            ob_start();
            var_dump($ResponsContents);
            $respons_array['ALLResponsContents'] = "REST API ALL Response:" . ob_get_contents();
            //----------------------------------------------------------------------
            //出力バッファをクリア(消去)する
            //----------------------------------------------------------------------
            ob_clean();

            ////////////////////////////////
            // 通信結果を判定               //
            ////////////////////////////////
            if( count( $http_response_header ) > 0 ){
                ////////////////////////////////
                // HTTPレスポンスコード取得          //
                ////////////////////////////////
                preg_match('/HTTP\/1\.[0|1|x] ([0-9]{3})/', $http_response_header[0], $matches);
                $status_code = $matches[1];

                ////////////////////////////////
                // 返却用のArrayを編集           //
                ////////////////////////////////
                $respons_array['StatusCode']      = ( int ) $status_code;
                $info = array();
                $info = json_decode( $ResponsContents, true );
                $respons_array['ResponsContents'] = $info;
            }
            else{
                ////////////////////////////////
                // 返却用のArrayを編集           //
                ////////////////////////////////
                $respons_array['StatusCode']      = ( int ) -2;
                $respons_array['ResponsContents'] = array( "ErrorMessage" => "HTTP Socket Timeout" );
            }
        }
        ////////////////////////////////
        // 結果を返却                   //
        ////////////////////////////////
       return $respons_array;
    }



    //////////////////////////////////////////////////////////////////////
    //
    //  【概要】
    //    Terraformドライバ RESTAPI実行関数
    //
    //////////////////////////////////////////////////////////////////////
    ///////////////////////////////
    // Organizationsの一覧を取得する//
    ///////////////////////////////
    function get_organizations_list($hostname, $token){
        //requestURI
        $requestURI = "api/v2/organizations";
        //method
        $method = "GET";

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            null //requestContents
        );

        return $restApiResponse;
    }

    /////////////////////////
    // Organizationを作成する//
    /////////////////////////
    function create_organization($hostname, $token, $organizationName, $emailAddress){
        //requestURI
        $requestURI = "api/v2/organizations";
        //method
        $method = "POST";

        //requestContents
        $requestContents = array(
            "data" => array(
                "type" => "organizations",
                "attributes" => array(
                    "name" => $organizationName,
                    "email" => $emailAddress
                ),
            ),
        );

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            $requestContents //requestContents
        );

        return $restApiResponse;
    }

    /////////////////////////
    // Organizationを更新する//
    /////////////////////////
    function update_organization($hostname, $token, $organizationName, $emailAddress){
        //requestURI
        $requestURI = "api/v2/organizations/" . $organizationName;
        //method
        $method = "PATCH";

        //requestContents
        $requestContents = array(
            "data" => array(
                "type" => "organizations",
                "attributes" => array(
                    "email" => $emailAddress
                ),
            ),
        );

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            $requestContents //requestContents
        );

        return $restApiResponse;
    }

    /////////////////////////
    // Organizationを削除する//
    /////////////////////////
    function delete_organization($hostname, $token, $organizationName){
        //requestURI
        $requestURI = "api/v2/organizations/" . $organizationName;
        //method
        $method = "DELETE";

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            null //requestContents
        );

        return $restApiResponse;
    }

    /////////////////////////////
    // Workspacesの一覧を取得する//
    /////////////////////////////
    function get_workspaces_list($hostname, $token, $organizationName){
        //requestURI
        $requestURI = "api/v2/organizations/" . $organizationName . "/workspaces";
        //method
        $method = "GET";

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            null //requestContents
        );

        return $restApiResponse;
    }


    //////////////////////
    // Workspaceを作成する//
    //////////////////////
    function create_workspace($hostname, $token, $organizationName, $workspaceName, $option){
        //requestURI
        $requestURI = "api/v2/organizations/" . $organizationName . "/workspaces";
        //method
        $method = "POST";

        //オプション
        $executionMode = true; //設定値に関係なくtrueにする。（ITA側ではこのオプションを設定できない仕様とする）
        //autoApply(true = [Auto apply] / false = [Manual apply])
        /*
        $autoApply = $option['applyMethod'];
        if($autoApply != null){
            $autoApply = ($autoApply == 1) ? true : false;
        }else{
            $autoApply = true; //設定値がnullの場合、true(Auto Apply)に設定
        }
        */
        $autoApply = true; //設定値に関係なくtrueにする。v1.6以降で設定できるよう改修予定あり。
        $terraformVersion = $option['terraformVersion'];
        $terraformWorkingDirectory = ""; //設定値に関係なく空欄にする。（ITA側ではこのオプションを設定できない仕様とする）

        //requestContents
        $requestContents = array(
            "data" => array(
                "type" => "workspaces",
                "attributes" => array(
                    "name" => $workspaceName,
                    "operations" => $executionMode,
                    "auto-apply" => $autoApply,
                    "terraform-version" => $terraformVersion,
                    "working-directory" => $terraformWorkingDirectory,
                ),
            ),
        );

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            $requestContents //requestContents
        );

        return $restApiResponse;
    }


    /////////////////////////
    // Workspaceを更新する//
    /////////////////////////
    function update_workspace($hostname, $token, $organizationName, $workspaceName, $option){
        //requestURI
        $requestURI = "api/v2/organizations/" . $organizationName . "/workspaces/" . $workspaceName;
        //method
        $method = "PATCH";

        //オプション
        $executionMode = true; //設定値に関係なくtrueにする。（ITA側ではこのオプションを設定できない仕様とする）
        //autoApply(true = [Auto apply] / false = [Manual apply])
        /*
        $autoApply = $option['applyMethod'];
        if($autoApply != null){
            $autoApply = ($autoApply == 1) ? true : false;
        }else{
            $autoApply = true; //設定値がnullの場合、true(Auto Apply)に設定
        }
        */
        $autoApply = true; //設定値に関係なくtrueにする。v1.6以降で設定できるよう改修予定あり。
        $terraformVersion = $option['terraformVersion'];
        $terraformWorkingDirectory = ""; //設定値に関係なく空欄にする。（ITA側ではこのオプションを設定できない仕様とする）

        //requestContents
        $requestContents = array(
            "data" => array(
                "type" => "workspaces",
                "attributes" => array(
                    "name" => $workspaceName,
                    "operations" => $executionMode,
                    "auto-apply" => $autoApply,
                    "terraform-version" => $terraformVersion,
                    "working-directory" => $terraformWorkingDirectory,
                ),
            ),
        );

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            $requestContents //requestContents
        );

        return $restApiResponse;
    }



    //////////////////////
    // Workspaceを削除する//
    //////////////////////
    function delete_workspace($hostname, $token, $organizationName, $workspaceName){
        //requestURI
        $requestURI = "api/v2/organizations/" . $organizationName . "/workspaces/" . $workspaceName;
        //method
        $method = "DELETE";

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            null //requestContents
        );

        return $restApiResponse;
    }


    ////////////////////////////////////////
    // Workspaceのstateバージョンの一覧を取得する//
    ///////////////////////////////////////
    function get_workspace_state_version($hostname, $token, $organizationName, $workspaceName, $page = 10){
        //requestURI
        $requestURI = "api/v2/state-versions?filter%5Bworkspace%5D%5Bname%5D=" . $workspaceName . "&filter%5Borganization%5D%5Bname%5D=" . $organizationName . "&page%5Bsize%5D=" . $page;

        //method
        $method = "GET";

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            null //requestContents
        );

        return $restApiResponse;
    }

    ////////////////////////////////////////
    // Workspaceの現在のstateバージョンを取得する//
    ///////////////////////////////////////
    function get_workspace_current_state_version($hostname, $token, $workspaceID){
        //requestURI
        $requestURI = "api/v2/workspaces/" . $workspaceID . "/current-state-version";
        //method
        $method = "GET";

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            null //requestContents
        );

        return $restApiResponse;
    }


    ////////////////////////////
    // Workspace変数の一覧を取得する//
    ////////////////////////////
    function get_workspace_var_list($hostname, $token, $workspaceID){
        //requestURI
        $requestURI = "api/v2/workspaces/" . $workspaceID . "/vars";
        //method
        $method = "GET";

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            null //requestContents
        );

        return $restApiResponse;
    }

    ////////////////////////////
    // Workspace変数を作成する//
    ////////////////////////////
    function create_workspace_var($hostname, $token, $workspaceID, $workspaceVarKey, $workspaceVarValue, $sensitiveFlag = false, $category = "terraform"){
        //requestURI
        $requestURI = "api/v2/workspaces/" . $workspaceID . "/vars";
        //method
        $method = "POST";

        //文字列型に変換
        $workspaceVarKey = (string) $workspaceVarKey;
        $workspaceVarValue = (string) $workspaceVarValue;

        //requestContents
        $requestContents = array(
            "data" => array(
                "type" => "vars",
                "attributes" => array(
                    "key" => $workspaceVarKey,
                    "value" => $workspaceVarValue,
                    "description" => "",
                    "category" => $category,
                    "hcl" => false,
                    "sensitive" => $sensitiveFlag,
                ),
            ),
        );

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            $requestContents //requestContents
        );

        return $restApiResponse;
    }

    ////////////////////////////
    // Workspace変数を削除する//
    ////////////////////////////
    function delete_workspace_var($hostname, $token, $workspaceID, $variableID){
        //requestURI
        $requestURI = "api/v2/workspaces/" . $workspaceID . "/vars/" . $variableID;
        //method
        $method = "DELETE";

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            null //requestContents
        );

        return $restApiResponse;
    }


    /////////////////////////////////////////////////////
    // Terraformコード（モジュール）をアップロードするためのURLを取得//
    ////////////////////////////////////////////////////
    function get_upload_url($hostname, $token, $workspaceID){
        //requestURI
        $requestURI = "api/v2/workspaces/" . $workspaceID . "/configuration-versions";
        //method
        $method = "POST";

        //requestContents
        //auto-queue-runsがtrueなら、アップロード後自動的にplan&applyが実行される
        $requestContents = array(
            "data" => array(
                "type" => "configuration-versions",
                "attributes" => array(
                  "auto-queue-runs" => false,
                  "speculative" => false
                ),
            ),
        );

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            $requestContents //requestContents
        );

        return $restApiResponse;
    }


    ////////////////////////////////////////
    // Terraformコード（モジュール）をアップロードする//
    ////////////////////////////////////////
    function module_upload($token, $uploadURL, $uploadFile){
        $method = "PUT";
        $Header = array( "Authorization: Bearer ". $token,
                         "Content-Type: application/vnd.api+json");

        //ファイル送信が必要であるため、cURLを利用する。
        $curl = curl_init($uploadURL);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $Header);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        //アップロードファイルをセット
        $data = file_get_contents($uploadFile);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $restApiResponse = curl_exec($curl);
        curl_close($curl);

        return $restApiResponse;
    }


    ////////////////
    // Runを作成する//
    ////////////////
    function create_run($hostname, $token, $workspaceID, $cv_id){
        //requestURI
        $requestURI = "api/v2/runs";

        //method
        $method = "POST";

        //requestContents
        $requestContents = array(
            "data" => array(
                "attributes" => array(
                  "is-destroy" => false,
                  "message" => ""
                ),
                "type" => "runs",
                "relationships" => array(
                    "workspace" => array(
                        "data" => array(
                            "type" => "workspaces",
                            "id" => $workspaceID
                        )
                    ),
                    "configuration-version" => array(
                        "data" => array(
                            "type" => "configuration-versions",
                            "id" => $cv_id
                        )
                    )
                ),
            ),
        );

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            $requestContents //requestContents
        );

        return $restApiResponse;
    }

    ////////////////////
    // Runの詳細を取得する//
    ////////////////////
    function get_run_data($hostname, $token, $runID){
        //requestURI
        $requestURI = "api/v2/runs/" . $runID;
        //method
        $method = "GET";

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            null //requestContents
        );

        return $restApiResponse;
    }

    ///////////////////////////////////////
    // Runに付随するpolicy-checkの一覧を取得する//
    ///////////////////////////////////////
    function get_run_policy_check_data($hostname, $token, $runID){
        //requestURI
        $requestURI = "api/v2/runs/" . $runID . "/policy-checks";
        //method
        $method = "GET";

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            null //requestContents
        );

        return $restApiResponse;
    }

    ////////////////////
    // RUNをキャンセルする//
    ////////////////////
    function cancel_run($hostname, $token, $runID){
        //requestURI
        $requestURI = "api/v2/runs/" . $runID . "/actions/cancel";

        //method
        $method = "POST";

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            null //requestContents
        );

        return $restApiResponse;
    }

    ////////////////////
    // Planの詳細を取得する//
    ////////////////////
    function get_plan_data($hostname, $token, $planID){
        //requestURI
        $requestURI = "api/v2/plans/" . $planID;
        //method
        $method = "GET";

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            null //requestContents
        );

        return $restApiResponse;
    }

    ////////////////////
    // Applyの詳細を取得する//
    ////////////////////
    function get_apply_data($hostname, $token, $applyID){
        //requestURI
        $requestURI = "api/v2/applies/" . $applyID;
        //method
        $method = "GET";

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            null //requestContents
        );

        return $restApiResponse;
    }

    ///////////////////////////////
    // Policy Setsの一覧を取得する//
    ///////////////////////////////
    function get_policy_sets_list($hostname, $token, $organizationName){
        //requestURI
        $requestURI = "api/v2/organizations/" . $organizationName . "/policy-sets";
        //method
        $method = "GET";

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            null //requestContents
        );

        return $restApiResponse;
    }

    ///////////////////////////////
    // Policy Setsを作成する//
    ///////////////////////////////
    function create_policy_set($hostname, $token, $organizationName, $policySetName, $policySetNote){
        //requestURI
        $requestURI = "api/v2/organizations/" . $organizationName . "/policy-sets";
        //method
        $method = "POST";

        //requestContents
        $requestContents = array(
            "data" => array(
                "type" => "policy-sets",
                "attributes" => array(
                    "name" => $policySetName,
                    "description" => $policySetNote,
                    "global" => false,

                ),
            ),
        );

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            $requestContents //requestContents
        );

        return $restApiResponse;
    }


    /////////////////////////
    // Policy Setsを更新する//
    /////////////////////////
    function update_policy_set($hostname, $token, $policySetID, $policySetName, $policySetNote){
        //requestURI
        $requestURI = "api/v2/policy-sets/" . $policySetID;
        //method
        $method = "PATCH";

        //requestContents
        $requestContents = array(
            "data" => array(
                "type" => "policy-sets",
                "attributes" => array(
                    "name" => $policySetName,
                    "description" => $policySetNote,
                    "global" => false,

                ),
            ),
        );

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            $requestContents //requestContents
        );

        return $restApiResponse;
    }

    //////////////////////////////
    // Policy SetsにWorkspaceを追加する//
    //////////////////////////////
    function relationships_workspace($hostname, $token, $policySetID, $workspaceData){
        //requestURI
        $requestURI = "api/v2/policy-sets/" . $policySetID . "/relationships/workspaces";
        //method
        $method = "POST";

        //requestContents
        $requestContents = $workspaceData;
        /* workspaceDataの例
        $workspaceData = array(
            "data" => array(
                array(
                    "id" => "ws-u3S5p2Uwk21keu1s",
                    "type" => "workspaces"
                ),
                array(
                    "id" => "ws-2HRvNs49EWPjDqT1",
                    "type" => "workspaces"
                ),
            ),
        );
        */

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            $requestContents //requestContents
        );

        return $restApiResponse;
    }

    //////////////////////////////
    // Policy SetsからWorkspaceを切り離す//
    //////////////////////////////
    function delete_relationships_workspace($hostname, $token, $policySetID, $workspaceData){
        //requestURI
        $requestURI = "api/v2/policy-sets/" . $policySetID . "/relationships/workspaces";
        //method
        $method = "DELETE";

        //requestContents
        $requestContents = $workspaceData;
        /* workspaceDataの例
        $workspaceData = array(
            "data" => array(
                array(
                    "id" => "ws-u3S5p2Uwk21keu1s",
                    "type" => "workspaces"
                ),
                array(
                    "id" => "ws-2HRvNs49EWPjDqT1",
                    "type" => "workspaces"
                ),
            ),
        );
        */

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            $requestContents //requestContents
        );

        return $restApiResponse;
    }

    //////////////////////////////
    // Policy SetsにPolicyを追加する//
    //////////////////////////////
    function relationships_policy($hostname, $token, $policySetID, $policyData){
        //requestURI
        $requestURI = "api/v2/policy-sets/" . $policySetID . "/relationships/policies";
        //method
        $method = "POST";

        //requestContents
        $requestContents = $policyData;
        /* policyDataの例
        $policyData = array(
            "data" => array(
                array(
                    "id" => "pol-u3S5p2Uwk21keu1s",
                    "type" => "policies"
                ),
                array(
                    "id" => "pol-2HRvNs49EWPjDqT1",
                    "type" => "policies"
                ),
            ),
        );
        */

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            $requestContents //requestContents
        );

        return $restApiResponse;
    }

    //////////////////////////////
    // Policy SetsからPolicyを削除する//
    //////////////////////////////
    function delete_relationships_policy($hostname, $token, $policySetID, $policyData){
        //requestURI
        $requestURI = "api/v2/policy-sets/" . $policySetID . "/relationships/policies";
        //method
        $method = "DELETE";

        //requestContents
        $requestContents = $policyData;
        /* policyDataの例
        $policyData = array(
            "data" => array(
                array(
                    "id" => "pol-u3S5p2Uwk21keu1s",
                    "type" => "policies"
                ),
                array(
                    "id" => "pol-2HRvNs49EWPjDqT1",
                    "type" => "policies"
                ),
            ),
        );
        */

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            $requestContents //requestContents
        );

        return $restApiResponse;
    }


    ///////////////////////////////
    // Policyの一覧を取得する//
    ///////////////////////////////
    function get_policy_list($hostname, $token, $organizationName){
        //requestURI
        $requestURI = "api/v2/organizations/" . $organizationName . "/policies";
        //method
        $method = "GET";

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            null //requestContents
        );

        return $restApiResponse;
    }

    ///////////////////////////////
    // Policysを作成する//
    ///////////////////////////////
    function create_policy($hostname, $token, $organizationName, $policyName, $policyFileName, $policyNote){
        //requestURI
        $requestURI = "api/v2/organizations/" . $organizationName . "/policies";
        //method
        $method = "POST";

        //requestContents
        $requestContents = array(
            "data" => array(
                "type" => "policies",
                "attributes" => array(
                    "enforce" => array(
                        array(
                            "path" => $policyFileName,
                            "mode" => "hard-mandatory"
                        )
                    ),
                    "name" => $policyName,
                    "description" => $policyNote,
                ),
            ),
        );

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            $requestContents //requestContents
        );

        return $restApiResponse;

    }

    ///////////////////////////////
    // Policysを更新する//
    ///////////////////////////////
    function update_policy($hostname, $token, $policyID, $policyName, $policyFileName, $policyNote){
        //requestURI
        $requestURI = "api/v2/policies/" . $policyID;

        //method
        $method = "PATCH";

        //requestContents
        $requestContents = array(
            "data" => array(
                "type" => "policies",
                "attributes" => array(
                    "enforce" => array(
                        array(
                            "path" => $policyFileName,
                            "mode" => "hard-mandatory"
                        )
                    ),
                    "name" => $policyName,
                    "description" => $policyNote,
                ),
            ),
        );

        //restApiResponse
        $restApiResponse = terraform_restapi_access(
            $hostname, //hostname
            $token, //token
            $requestURI, //requestURI
            $method, //method
            $requestContents //requestContents
        );

        return $restApiResponse;

    }


    ///////////////////////////////
    // Policy素材をアップロードする//
    ///////////////////////////////
    function policy_file_upload($hostname, $token, $policyID, $targetPolicyMatterPath){
        //返却用array
         $respons_array = array();

        //requestURI
        $requestURI = "https://" . $hostname . "/api/v2/policies/" . $policyID . "/upload";

        //method
        $method = "PUT";

        $Header = array( "Authorization: Bearer ". $token,
                         "Content-Type: application/octet-stream");

        //ファイル送信が必要であるため、cURLを利用する。
        $curl = curl_init($requestURI);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $Header);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        //アップロードファイルをセット
        $data = file_get_contents($targetPolicyMatterPath);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $restApiResponse = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        //出力のバッファリングをオンに設定
        ob_start();
        var_dump($restApiResponse);
        $respons_array['ALLResponsContents'] = "REST API ALL Response:" . ob_get_contents();

        //出力バッファをクリア(消去)する
        ob_clean();

        //各データをセット
        $respons_array['StatusCode'] = ( int ) $httpcode;
        $info = array();
        $info = json_decode( $restApiResponse, true);
        $respons_array['ResponsContents'] = $info;

        return $respons_array;

    }


?>
