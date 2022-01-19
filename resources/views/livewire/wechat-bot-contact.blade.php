<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        通讯录
    </h2>
</x-slot>
<div class="max-w-7xl mx-auto py-10 flex-col space-y-4">
    <!-- Top Bar -->
    <div class="flex justify-between">
        <div class="w-2/4 flex space-x-4 relative">
            <x-input.text wire:model.debounce.1000ms="filters.search" placeholder="Search ..." class="pl-10 pr-5 appearance-none h-10 w-full rounded-full text-sm focus:outline-none" />
            <button type="submit" class="absolute top-0 mt-3 left-0 ml-4">
                <x-icon.search />
            </button>

            <x-input.group borderless paddingless for="type" label="显示类型">
                <x-input.select wire:model="type" id="type">
                    <option value="friend">好友</option>
                    <option value="group">群</option>
                    <option value="public">公众号</option>
                </x-input.select>
            </x-input.group>
            
        </div>

        <div class="space-x-2 flex items-center">
            <x-input.group borderless paddingless for="perPage" label="Per Page">
                <x-input.select wire:model="perPage" id="perPage">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </x-input.select>
            </x-input.group>
        </div>
    </div>

    <!-- Table -->
    <x-table>
        <x-slot name="head">
            <x-table.heading class="pr-0 w-8">
                <x-input.checkbox wire:model="selectPage" />
            </x-table.heading>
            <x-table.heading  multi-column >Avatar</x-table.heading>
            <x-table.heading  multi-column class="w-full">备注</x-table.heading>
            <x-table.heading  multi-column >Signature</x-table.heading>
            <x-table.heading  multi-column >Sex</x-table.heading>
            <x-table.heading  multi-column >From</x-table.heading>
            <x-table.heading />
        </x-slot>

        <x-slot name="body">
            @if ($selectPage)
                <x-table.row class="bg-cool-gray-200" wire:key="row-message">
                    <x-table.cell colspan="6">
                        @unless($selectAll)
                            <div>
                                <span>You have selected <strong>{{ $models->count() }}</strong> items, do you want to select
                                    all <strong>{{ $models->total() }}</strong>?</span>
                                <x-button.link wire:click="selectAll" class="ml-1 text-blue-600">Select All</x-button.link>
                            </div>
                        @else
                            <span>You are currently selecting all <strong>{{ $models->total() }}</strong> items.</span>
                @endif
                </x-table.cell>
                </x-table.row>
                @endif

                @forelse ($models as $wechatBotContact)
                    <x-table.row wire:loading.class.delay="opacity-50" wire:key="row-{{ $wechatBotContact->id }}">
                        <x-table.cell class="pr-0">
                            <x-input.checkbox wire:model="selected" value="{{ $wechatBotContact->id }}" />
                        </x-table.cell>

                        <x-table.cell>
                            <span href="#" class="inline-flex space-x-2 truncate text-sm leading-5">
                                <p class="text-cool-gray-600 truncate">
                                    <img 
                                        title="{{ $wechatBotContact->contact->nickname}}" 
                                        alt="{{ $wechatBotContact->contact->wxid}}" class="rounded-xl w-14" 
                                        src="{{$wechatBotContact->contact->avatar?$wechatBotContact->contact->avatar:$defaultAvatar}}">
                                </p>
                            </span>
                        </x-table.cell>

                        <x-table.cell>
                            <p class="text-cool-gray-600">
                                {{ $wechatBotContact->remark  }}
                            </p>
                        </x-table.cell>

                        <x-table.cell>
                            <div class="max-w-lg m-6">
                                <div class="relative">
                                  <div class="hidden">
                                    <div class="absolute z-40 left-0 mt-2 w-full">
                                      <div class="py-1 text-sm bg-white rounded shadow-lg border border-gray-300">
                                        <a class="block py-1 px-5 cursor-pointer hover:bg-indigo-600 hover:text-white">Add tag "<span class="font-semibold" x-text="textInput"></span>"</a>
                                      </div>
                                    </div>
                                  </div>
                                  <!-- selections -->
                                    <div id="need-to-be-changed">
                                    @isset($tags[$wechatBotContact->id])
                                        @foreach ($tags[$wechatBotContact->id] as $tagId =>$tagName)
                                        <div class="bg-blue-100 inline-flex items-center text-sm rounded mt-2 p-2 mr-1 overflow-hidden">
                                            <span class="ml-2 mr-1 leading-relaxed truncate max-w-xs px-1">{{$tagName}}</span>
                                        </div>
                                        @endforeach
                                    @endisset
                                    </div>
                                </div>
                            </div>

                            <p class="text-cool-gray-600 truncate">
                                <span class="pl-4 text-gray-600 font-medium">{{ $wechatBotContact->contact->signature }} </span>
                            </p>
                        </x-table.cell>

                        <x-table.cell>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium leading-4 bg-{{ $wechatBotContact->status_color }}-100 text-{{ $wechatBotContact->status_color }}-800 capitalize">
                                {{ App\Models\WechatContact::SEX[$wechatBotContact->contact->sex] }}
                            </span>
                        </x-table.cell>

                        <x-table.cell>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium leading-4 bg-{{ $wechatBotContact->status_color }}-100 text-{{ $wechatBotContact->status_color }}-800 capitalize">
                                {{ $wechatBotContact->contact->province }}
                            </span>
                        </x-table.cell>
                        <x-table.cell>
                            <x-button.link wire:click="edit({{ $wechatBotContact->id }})">Edit</x-button.link>
                        </x-table.cell>

                    </x-table.row>
                @empty
                    <x-table.row>
                        <x-table.cell colspan="6">
                            <div class="flex justify-center items-center space-x-2">
                                <x-icon.inbox class="h-8 w-8 text-cool-gray-400" />
                                <span class="font-medium py-8 text-cool-gray-400 text-xl">No items found...</span>
                            </div>
                        </x-table.cell>
                    </x-table.row>
                @endforelse
        </x-slot>
    </x-table>

    <div>
        {{ $models->links() }}
    </div>

    <!-- Delete Modal -->
    <form wire:submit.prevent="deleteSelected">
        <x-modal.confirmation wire:model.defer="showDeleteModal">
            <x-slot name="title">Delete</x-slot>

            <x-slot name="content">
                <div class="py-8 text-cool-gray-700">Are you sure you? This action is irreversible.</div>
            </x-slot>

            <x-slot name="footer">
                <x-button.secondary wire:click="$set('showDeleteModal', false)">Cancel</x-button.secondary>

                <x-button.primary type="submit">Delete</x-button.primary>
            </x-slot>
        </x-modal.confirmation>
    </form>

    <!-- Save Modal -->
    <form wire:submit.prevent="save">
        <x-modal.dialog wire:model.defer="showEditModal">
            <x-slot name="title">Edit</x-slot>

            <x-slot name="content">
                <x-input.group for="remark" label="备注名" :error="$errors->first('editing.remark')">
                    <x-jet-input name="remark" type="text" class="mt-1 block w-full" 
                        wire:model="editing.remark" 
                        value="{{$editing->remark}}"
                        wire:keydown.enter.prevent.stop="updateRemark({{$editing->id}}, $event.target.value)" 
                        autocomplete="remark"/>
                </x-input.group>

                <x-input.group for="tag" label="添加标签">
                    <x-jet-input name="tag" type="text" placeholder="输入新标签并回车,无需点击保存按钮" class="mt-1 block w-full" 
                        wire:model="editTag"
                        wire:keydown.enter.prevent.stop="attachTag({{$editing->id}}, $event.target.value)" />
                </x-input.group>

                <div id="need-to-be-changed">
                    @isset($tags[$editing->id])
                        @foreach ($tags[$editing->id] as $tagId =>$tagName)
                        <div class="bg-blue-100 inline-flex items-center text-sm rounded mt-2 mr-1 overflow-hidden">
                            <span id="tag-{{$tagId}}" class="ml-2 mr-1 leading-relaxed truncate max-w-xs px-1">{{$tagName}}</span>
                            <button class="w-6 h-8 inline-block align-middle text-gray-500 bg-blue-200 focus:outline-none"
                            wire:click.prevent.stop="detachTag({{$editing->id}},'{{$tagName}}')">
                                <svg class="w-6 h-6 fill-current mx-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M15.78 14.36a1 1 0 0 1-1.42 1.42l-2.82-2.83-2.83 2.83a1 1 0 1 1-1.42-1.42l2.83-2.82L7.3 8.7a1 1 0 0 1 1.42-1.42l2.83 2.83 2.82-2.83a1 1 0 0 1 1.42 1.42l-2.83 2.83 2.83 2.82z"/></svg>
                            </button>
                        </div>
                        @endforeach
                    @endisset
                </div>
                

            </x-slot>

            <x-slot name="footer">
                <div class="flex justify-between">
                    <div>
                        @if($type=='friend')
                        <x-button.secondary wire:click="friendDel({{$editing->id}})">拉黑</x-button.secondary>
                        <span class="text-sm text-gray-600">⚠️即删除微信好友⚠️</span>
                        @endif

                        @if($type=='group')
                        <label for="log_me" class="flex items-center">
                            <x-jet-checkbox wire:model.defer="isListenRoom" :value="$isListenRoom"/>
                            <span class="ml-2 text-sm text-gray-600">监听本群消息</span>
                        </label>
                        <label for="log_me" class="flex items-center">
                            <x-jet-checkbox wire:model.defer="isReplyRoom" :value="$isReplyRoom"/>
                            <span class="ml-2 text-sm text-gray-600">响应本群关键词</span>
                        </label>
                        @endif
                    </div>
                    
                    <div>
                        <x-button.secondary wire:click="$set('showEditModal', false)">Cancel</x-button.secondary>
                        <x-button.primary type="submit">Save</x-button.primary>
                    </div>
                </div>
            </x-slot>
        </x-modal.dialog>
    </form>
</div>
