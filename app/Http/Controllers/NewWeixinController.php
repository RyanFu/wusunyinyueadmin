<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use EasyWeChat\Factory;
use App\Models\Wx_weixins;
use App\Models\Wx_config;
use App\Models\Article;

use EasyWeChat\OfficialAccount\Application;

class NewWeixinController extends Controller
{
	// 设置默认
    public $default_returnnum;
    public $default_welcometext;//关注回复
    public $default_defaulttext;//默认回复
    public $laststr;//最后的结语
    public $returnCategory;
    
    public function index($id){
    	$weixin = Wx_weixins::findOrFail($id);
    	
    	$this->default_welcometext = $weixin->welcometext?:(configs('微信系统默认关注回复语'));
    	$this->default_defaulttext = $weixin->defaulttext?:(configs('微信未识别的回复'));
    	$this->default_returnnum = $weixin->returnnum?:(configs('微信默认回复歌曲数量'));
    	$this->laststr = $weixin->laststr?:(configs('laststr'));
    	$this->returnCategory = $weixin->returnCategory?:(configs('returnCategory'));
        
        $config = [
            'app_id' => $weixin->AppID,
            'secret' => $weixin->AppSecret,
            'token' => $weixin->Token,
            'aes_key' => $weixin->EncodingAESKey,
            //...
        ];
		$app = Factory::officialAccount($config);
		
		$app->server->push(function ($message) {
            switch ($message['MsgType']) {
                case 'event':
                    return $this->default_welcometext;
                    break;
                case 'text':
                	// $openid = $server->get($message['FromUserName']);
                	// return $openid ;
                    return $this->search($message['Content'],$this->default_returnnum);
                    break;
                default:
                    return $this->default_defaulttext;
                    break;
            }
        });
		$response = $app->server->serve();
		
		return $response;
    }

		// 根据用户的输入，提供相应的服务，搜索歌曲，返回给用户。
    public function search($keyword,$num){
        $mess = '';
        $categoryArr = explode(',',$this->returnCategory);
        $articles = Article::where('title', 'like', '%' . $keyword . '%')->whereIn('category_id',$categoryArr)->orWhere('author', 'like', '%' . $keyword . '%')->orderBy('hits', 'desc')->orderBy('id', 'desc')->limit($num)->get();
        if(empty($articles) or empty(count($articles))){
        	$mess = $this->default_defaulttext;
        	return $mess;
        }
        foreach($articles as $a){
            $mess.="歌曲名称:<a href='".$a->downlink."'>".$a->title."</a>\n歌手:".$a->author."\n提取码:".$a->downpassword."\n\n";
        }
        $mess = rtrim($mess);
        $mess.="\n".$this->laststr;
        return $mess;
    }
    
    public function send_get($url){
    	$html = file_get_contents($url);
		return $html;
    }
    
    public	function send_post($url, $post_data) {
		  $postdata = http_build_query($post_data);
		  $options = array(
		    'http' => array(
		      'method' => 'POST',
		      'header' => 'Content-type:application/x-www-form-urlencoded',
		      'content' => $postdata,
		      'timeout' => 15 * 60 // 超时时间（单位:s）
		    )
		  );
		  $context = stream_context_create($options);
		  $result = file_get_contents($url, false, $context);
		  return $result;
	}
}
