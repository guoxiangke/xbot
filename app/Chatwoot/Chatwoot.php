<?php

namespace App\Chatwoot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use App\Models\WechatBotContact;
use App\Models\WechatBot;

class Chatwoot
{
	public $baseUrl;
    public $api_version = 'api/v1';
    public $http;
    public $inboxId; // @see http://100.72.181.69:3000/app/accounts/6/settings/inboxes/list
    public $accountId;
    public $token;
    function __construct(WechatBot $wechatBot) {
    	$accounts = [
		    '7' => [// xiaoyong
		    	'accountId' => 1,
		    	'inboxId' => 1,
		    	'token' => '',
		    	'baseUrl' => 'https://51chat.net',
		    ],
		    '26' => [// lu
		    	'accountId' => 2,
		    	'inboxId' => 2,
		    	'token' => '',
		    	'baseUrl' => 'https://51chat.net',
		    ],
		    '1' => [// you4
		    	'accountId' => 3,
		    	'inboxId' => 3,
		    	'token' => '',
		    	'baseUrl' => 'https://51chat.net',
		    ],
		    '13' => [// luke
		    	'accountId' => 2,
		    	'inboxId' => 1,
		    	'token' => 'RutaakVSGLnhvo3Abbtj7tYL',
		    	'baseUrl' => 'https://51chat.net',
		    ],
		    
	    ];
    	$this->accountId = $accounts[$wechatBot->id]['accountId'];
    	$this->inboxId = $accounts[$wechatBot->id]['inboxId'];
    	$this->token = $accounts[$wechatBot->id]['token'];
    	$this->baseUrl = $accounts[$wechatBot->id]['baseUrl'];
    	$headers = [
	      'Content-Type' => 'application/json',
	      'api_access_token' => $this->token
	    ];
        $this->http = Http::withHeaders($headers);
    }

    public function getContactByWxid(string $wxid)
    {
    	// http://100.72.181.69:3000/api/v1/accounts/6/contacts/search
		$url = "{$this->baseUrl}/{$this->api_version}/accounts/{$this->accountId}/contacts/search";
	    $body = [
	        'q' => $wxid,
	        'sort' => "phone_number",
	        'page' => 1,
	    ];
	    $response = $this->http->get($url, $body);
		return $contact = $response['payload'][0]??null;
    	// return json_decode($response);
    	// return $contact['id'];
    	// return $contact['additional_attributes	{}
		// return $contact['availability_status	"offline"
		// return $contact['email	"contact7@53ichat.com"
		// return $contact['name	"contact7"
		// return $contact['phone_number	"+137171245678"
		// return $contact['identifier	"wx_123456"
		// return $contact['thumbnail	""
		// return $contact['custom_attributes	{}
		// return $contact['last_activity_at	1713241458
		// return $contact['created_at	1713205776
    	// return $contact['contact_inboxes'][0]['source_id'];
    	// return $contact['contact_inboxes'][0]['inbox']['id'];
    	// return $contact['contact_inboxes'][0]['inbox']['inbox_identifier'];
    }
    public function getContactById(int $id)
    {
    	// Get a contact belonging to the account using ID
    	// https://app.chatwoot.com/api/v1/accounts/{account_id}/contacts/{id}
		$url = "{$this->baseUrl}/{$this->api_version}/accounts/{$this->accountId}/contacts/{$id}";
	    return $contact = $response = $this->http->get($url)->json()['payload']??null;
	    // $sourceId = $contact['contact_inboxes'][0]['source_id'];
    	// return json_decode($response);
    	// return $contact['id'];
    	// return $contact['additional_attributes	{}
		// return $contact['availability_status	"offline"
		// return $contact['email	"contact7@53ichat.com"
		// return $contact['name	"contact7"
		// return $contact['phone_number	"+137171245678"
		// return $contact['identifier	"wx_123456"
		// return $contact['thumbnail	""
		// return $contact['custom_attributes	{}
		// return $contact['last_activity_at	1713241458
		// return $contact['created_at	1713205776
    	// return $contact['contact_inboxes'][0]['source_id'];
    	// return $contact['contact_inboxes'][0]['inbox']['id'];
    	// return $contact['contact_inboxes'][0]['inbox']['inbox_identifier'];
    }

    public function saveContact(WechatBotContact $wechatBotContact)
    {
    	$wxid = $wechatBotContact->wxid;
    	// search first, if has, return it/update it, no need create!
		$contact = $this->getContactByWxid($wxid);
		if(!is_null($contact)){
			Log::debug(__FUNCTION__, ['ALREADY', $wxid, $contact['id'], $contact['name']]);
			return $contact; // or update contact!
		}
    	// ContactCreateA and return!
		// http://100.72.181.69:3000/api/v1/accounts/6/contacts
	    $url = "{$this->baseUrl}/{$this->api_version}/accounts/{$this->accountId}/contacts";
	    // $wechatBotContact = WechatBotContact::where('wxid', $wxid)->firstOrFail();
	    $contact = $wechatBotContact->contact;
	    $isRoom = $contact->isRoom;
		$name = $wechatBotContact->remark??$contact->nickname;
	    if(!$isRoom){
			$email = "{$wxid}@wx"; // wxid_xxx22@wx
	    }else{
	    	$email = "{$wxid}";	//"748242461@chatroom"
	    	$name = "群聊:".$name;
	    }
	    $body = [
		  "inbox_id" => $this->inboxId,
		  "name" => $name,
		  "email" => $email,
		  "company" => $wechatBotContact->wechatBot->name,
		  // "phone_number" => "+137171245678",
		  "avatar_url" => $contact->avatar,
		  // 'avatar' => file_get_contents($contact->avatar),
		  "identifier" => $wxid,
		  "custom_attributes" => [
		  	"sex" => match ($contact->sex) {
		  		0 => '未知',
		  		1 => '男',
		  		2 => '女',
		  	},
		  	"nickname" => $contact->nickname,
		  	"country" => $contact->country,
			"city" => $contact->city,
			"province" => $contact->province,
		  	"remark" =>$wechatBotContact->remark,
		  ],
		  "additional_attributes" => [
		  	"avatar_url" => $contact->avatar,
			"is_room" => $contact->isRoom?1:0,
		  	"sex" => $contact->sex,//'0未知，1男，2女'
		  	"wechat_bot_id" => $wechatBotContact->wechat_bot_id,
		  	"wechat_contact_id" =>$wechatBotContact->wechat_contact_id,
		  ],
	    ];
	    $res = $this->http->post($url, $body)->json();
	    if(isset($res['attributes']['email'])) { //再找一次
	    	return $contact = $this->getContactByWxid($wxid);
	    }
	    $res['payload']['contact']??Log::error(__FUNCTION__, [$wxid, $res]);
	    // array:2 [
		//   "message" => "Email has already been taken, Identifier has already been taken"
		//   "attributes" => array:2 [
		//     0 => "email"
		//     1 => "identifier"
		//   ]
		// ]
	    return $res['payload']['contact']??$res;
    }


    // update avatar!
    // if(!$contact['payload']['thumbnail']){}
    public function updateContactAvatar(Array $contact){
    	$contactId = $contact['id'];
    	$avatarUrl = $contact['additional_attributes']['avatar_url'];
    	$url = "{$this->baseUrl}/{$this->api_version}/accounts/{$this->accountId}/contacts/{$contactId}";
    	$body = [
		  "avatar_url" => $avatarUrl,
	    ];
	    return $this->http->put($url, $body)->json();
    }

    public function updateContactName($contactId, $name){
    	$url = "{$this->baseUrl}/{$this->api_version}/accounts/{$this->accountId}/contacts/{$contactId}";
    	$body = [
		  "name" => $name,
	    ];
	    return $this->http->put($url, $body)->json();
    }

    public function getConversation($contact){
    	$url = "{$this->baseUrl}/{$this->api_version}/accounts/{$this->accountId}/conversations";
		$sourceId = $contact['contact_inboxes'][0]['source_id'];
		$body = [
			"source_id" => $sourceId,
			"inbox_id" => $this->inboxId, //Allowed Inbox Types: Website, Phone, Api, Email 
			// 'message' =>  [
		    //     'content' => $content,
		    // ]
	    ];
		return	$conversation = $this->http->post($url, $body)->json();
    	// $conversation['id'];
    	// $contact = $conversation['meta']['sender']; 
    	// $conversation['messages'][0];
    	// $conversation['last_non_activity_message'];
    }

    // $isHost = false, 接受消息，传到chatwoot
    // $isHost = true, 第一次创建对话，不发消息给微信用户，只记录到chatwoot

    public function sendMessageToContact($contact, $content='hi', $isHost=false)
    {
    	// Get conversations associated to that contact
    	// https://app.chatwoot.com/api/v1/accounts/{accountId}/contacts/{id}/conversations
    	$url = "{$this->baseUrl}/{$this->api_version}/accounts/{$this->accountId}/contacts/{$contact['id']}/conversations";
    	$conversation = $this->http->get($url)->json()['payload'][0]??$this->getConversation($contact);
    	if(!$isHost) $this->_sendMessageToConversation($conversation, $content, $isHost);
    }

    // Create New Message
    // http://100.72.181.69:3000/api/v1/accounts/6/conversations/1/messages
    public function _sendMessageToConversation($conversation, $content="hi", $isHost=false)
    {
	    $url = "{$this->baseUrl}/{$this->api_version}/accounts/{$this->accountId}/conversations/{$conversation['id']}/messages";
	    $body = [
	        'content' => $content,
	        'message_type' => $isHost?"outgoing":"incoming",
	    ];
	    return $this->http->post($url, $body);
    }

    // https://github.com/chatwoot/chatwoot/issues/7501
    public function getLabels()
    {
	    $url = "{$this->baseUrl}/{$this->api_version}/accounts/{$this->accountId}/labels";
	    return $this->http->get($url)->json()['payload']??null;
    }

    public function getLabelsByContact($contact)
    {
	    $url = "{$this->baseUrl}/{$this->api_version}/accounts/{$this->accountId}/contacts/{$contact['id']}/labels";
	    return $this->http->get($url)->json()['payload']??null;
    }

    public function setLabelByContact($contact, $label="群聊")
    {
	    $url = "{$this->baseUrl}/{$this->api_version}/accounts/{$this->accountId}/contacts/{$contact['id']}/labels";
		$body = [
	        'labels' => [$label],
	    ];
	    $this->getOrCreateALabel($label);// make sure the lable exsits before set
	    return $this->http->post($url, $body)->json()['payload']??null;
    }

    public function getOrCreateALabel($label="群聊")
    {
    	$labels = $this->getLabels();
    	if($labels){
	    	// $lables = Arr::keyBy($labels, 'title'); // laravel 8 no keyBy
	    	$key = array_search($label, array_column($labels, 'title'));
	    	if($key!=false){
	    		return $labels[$key];
	    	}
    	}
    	// $isNeedCreate = true;
	    $url = "{$this->baseUrl}/{$this->api_version}/accounts/{$this->accountId}/labels";
	    $body = [
	        'title' => $label,
	        'description' => 'Created by System, You can change the color and description.',
	        'color' => "#666666",
	        'show_on_sidebar' => true,
	    ];
	    return $this->http->post($url,$body)->json()??['null'];

    }
}
