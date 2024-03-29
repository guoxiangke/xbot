<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\WechatBotContact as Model;
use App\Http\Livewire\DataTable\WithSorting;
use App\Http\Livewire\DataTable\WithCachedRows;
use App\Http\Livewire\DataTable\WithBulkActions;
use App\Http\Livewire\DataTable\WithPerPagePagination;
use App\Models\WechatBot;
use App\Models\WechatContact;
use App\Models\WechatMessage;
use Illuminate\Support\Facades\Log;
use Spatie\Tags\Tag;

use App\Models\WechatBotContact;

// 通讯录管理
class WechatBotContactTag extends Component
{
    public int $wechatBotId;

    public $tags;
    public $tagModels;

    public string $defaultAvatar = Model::DEFAULT_AVATAR;
    
    public function render()
    {
        $models = $this->rows;
        $tags = [];
        $models->each(function($model) use(&$tags){
            $model->tags->each(function($tag) use($model,&$tags){
                $tags[$model->id][$tag->id] = $tag->name;
            });
        });
        $this->tags = $tags;

        return view('livewire.wechat-bot-contact-tag', compact('models'));
    }
 

    use WithPerPagePagination, WithSorting, WithBulkActions, WithCachedRows;

    public $showDeleteModal = false;
    public $showEditModal = false;
    public $model = Model::class;
    public $filters = [
        'search' => '',
    ];
    public Model $editing;

    public $tagId = 0;
    public $tag;
    protected $queryString = ['sorts','tag'];

    protected $listeners = ['refreshWechatContacts' => '$refresh'];

    public $editTag = '';
    public function rules()
    {
        return [
            'editing.remark' => 'required|min:3',
            'editing.seat_user_id' => 'required',
            // 'editing.tag' => 'sometimes',
        ];
    }

    public $tagWith;
    public WechatBot|null $wechatBot;
    public function mount()
    {
        $currentTeamOwnerId = auth()->user()->currentTeam->owner->id;
        $this->wechatBot = WechatBot::where('user_id', $currentTeamOwnerId)->first();
        if(!$this->wechatBot) abort(403, '当前账户暂未绑定wxid, 请与管理员联系！');
        $this->wechatBotId = $this->wechatBot->id;
        $this->editing = $this->makeBlankModel();
        $this->tagWith =  'wechat-contact-team-'.$currentTeamOwnerId;
        $this->isListenRooms = $this->wechatBot->getMeta('isListenRooms', []);
        $this->isReplyRooms = $this->wechatBot->getMeta('isReplyRooms', []);
        $this->isListenMemberChangeRooms = $this->wechatBot->getMeta('isListenMemberChangeRooms', []);
        $this->RoomWelcomeMessages = $this->wechatBot->getMeta('roomWelcomeMessages', []);

        $this->tagModels = Tag::getWithType($this->tagWith);
        $this->tagId =  $this->tagModels->first()->id;
    }

    public function friendDel(Model $wechatBotContact)
    {
        // $wxid = $wechatBotContact->contact->wxid;
        // $response = $this->wechatBot->friendDel($wxid);
        // Log::info(__METHOD__, [$wxid]);
        // if($response->ok() && $response['code'] == 1000){
        //     // 删除所有的消息
        //     // ->messages->each(fn($item)=>$item->forceDelete())
        //     // $user->posts()->each->delete();
        //     // $w->messages->each->forceDelete();
        //     // $wechatBotContact->messages()->delete();
        //     WechatMessage::where('wechat_bot_id', $this->wechatBot->id)
        //         ->where('conversation', $wechatBotContact->wechat_contact_id)
        //         ->delete();
        //     $wechatBotContact->delete();
        //     $this->dispatchBrowserEvent('notify', 'Deleted!');
        // }else{
        //     Log::error(__METHOD__, $response->json());
        // }
        $this->showEditModal = false;
    }

    public function detachTag(Model $wechatBotContact, string $tagName)
    {
        $wechatBotContact->detachTag($tagName, $this->tagWith);
    }

    public function attachTag(Model $wechatBotContact, string $tagName)
    {
        $tagName = trim($tagName);
        if($tagName) {
            $tag = Tag::findOrCreate($tagName, $this->tagWith);
            $wechatBotContact->attachTag($tagName, $this->tagWith);
        }
        $this->editTag = '';
    }

    public function updatedFilters()
    {
        $this->resetPage();
    }

    public function makeBlankModel()
    {
        return Model::make(['date' => now(), 'status' => 'success']);
    }

    public $isListenRoom = false;
    public $isReplyRoom = false;
    public $isListenRoomMemberChange = false;
    public $RoomWelcome = '';
    public $roomWxid;
    public $isListenRooms;
    public $isReplyRooms;
    public $isListenMemberChangeRooms;
    public $RoomWelcomeMessages;
    public $RoomWelcomeMessage;

    
    public function edit(Model $model)
    {
        $this->useCachedRows();
        if ($this->editing->isNot($model)) $this->editing = $model;
        $this->roomWxid = $model->contact->wxid; // "26401104290@chatroom"
        $this->isListenRoom = $this->isListenRooms[$this->roomWxid]??false;
        $this->isReplyRoom = $this->isReplyRooms[$this->roomWxid]??false;
        $this->isListenRoomMemberChange = $this->isListenMemberChangeRooms[$this->roomWxid]??false;
        $this->RoomWelcomeMessage = $this->RoomWelcomeMessages[$this->roomWxid]??'';
        $this->showEditModal = true;
    }

    public function save()
    {
        $this->validate();
        $this->editing->save();
        if($this->editTag){
            $tagName = trim($this->editTag);
            if($tagName) {
                Tag::findOrCreate($tagName, $this->tagWith);
                $this->editing->attachTag($tagName, $this->tagWith);
                $this->editTag = '';
            }
        }

        $this->showEditModal = false;

        $this->dispatchBrowserEvent('notify', 'Saved!');
    }

    public function updated($name,$value)
    {
    }

    public function resetFilters()
    {
        $this->reset('filters');
    }

    public function getRowsQueryProperty()
    {
        $tag = Tag::find($this->tagId);
        $this->tag = $tag;
        $query = WechatBotContact::query()
            ->withAnyTags($tag)
            ->with('contact')
            ->with('tags')
            ->when($this->filters['search'], fn($query, $search) => $query->where('remark', 'like', '%' . $search . '%'))
            ->orderBy('created_at', 'desc');
        return $this->applySorting($query);
    }

    public function getRowsProperty()
    {
        return $this->cache(function () {
            return $this->applyPagination($this->rowsQuery);
        });
    }
}
