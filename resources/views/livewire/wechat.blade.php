<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        状态设置
    </h2>
</x-slot>

<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8  space-y-4" @if(!$isLive) wire:poll.60000ms @endif>
    
    <div class="info">
        {{$msg}}
        @if($isLive)
        <figure class="md:flex bg-gray-100 rounded-xl p-8 md:p-0">
            <img class="w-36 h-36  mx-auto" src="{{ $xbotInfo['avatar']??$defaultAvatar }}" alt="{{ $xbotInfo['nickname'] }}" title="{{ $xbotInfo['nickname'] }}的头像">
            <div class="pt-0 md:p-8 space-y-4">
                <blockquote>
                    <p class="text-lg font-semibold">{{ $xbotInfo['nickname'] }} ({{ $xbotInfo['wxid'] }})</p>
                </blockquote>
                <figcaption class="font-medium">
                    <div class="text-gray-500">
                        <br/>上线时间：{{ $loginAt }}
                        <br/>上线设备: {{ $wechatBot->client_id }}号端口@Windows{{ $wechatBot->wechat_client_id }}
                        <br/>有效期： {{ $wechatBot->expires_at }}
                    </div>
                    
                    <div class="mt-4">
                        <button wire:click="logout" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150">退出Bot登录</button>
                    </div>    
                </figcaption>
            </div>
        </figure>
        @else
         <img class="w-36 h-36  mx-auto" src="{{ $loginQr }}">
        @endif


        @if($isLive)
        <div>
            <x-input.toggle 
                wire:model="config.isAutoAgree"
                id="config.isAutoAgree" 
                :checked="$config['isAutoAgree']"
                label="自动同意好友请求"
                />
            <x-input.toggle 
                wire:model="config.isWelcome"
                id="config.isWelcome" 
                :checked="$config['isWelcome']"
                label="发送加好友欢迎信息"
                />
            @if($config['isWelcome'])
            <x-input.group for="weclomeMsg" label="加好友欢迎信息">
                <textarea id="weclomeMsg" class="mt-1 block w-full"  autocomplete="weclomeMsg" 
                    wire:model="config.weclomeMsg"
                    wire:blur.stop="$set('config.weclomeMsg', $event.target.value)"
                    ></textarea>
            </x-input.group>
            @endif
            <x-input.toggle 
                wire:model="config.isListenRoom"
                id="config.isListenRoom" 
                :checked="$config['isListenRoom']"
                label="是否记录群消息"
                />
            @if($config['isListenRoom'])
            <x-input.toggle 
                wire:model="config.isListenRoomAll"
                id="config.isListenRoomAll" 
                :checked="$config['isListenRoomAll']"
                label="是否记录所有的群消息"
                />
            @endif

            <x-input.toggle 
                wire:model="config.isAutoReply"
                id="config.isAutoReply" 
                :checked="$config['isAutoReply']"
                label="配置关键词自动回复(仅好友，群需要另外配置)"
                />

            <x-input.toggle
                wire:model="config.isResourceOn"
                id="config.isResourceOn" 
                :checked="$config['isResourceOn']"
                label="X-resources资源关键词自动回复"
                />

            <x-input.toggle 
                wire:model="config.isAutoWcpay"
                id="config.isAutoWcpay" 
                :checked="$config['isAutoWcpay']"
                label="自动收款"
                />
            <x-input.toggle 
                wire:model="config.isWebhook"
                id="config.isWebhook" 
                :checked="$config['isWebhook']"
                label="是否开启消息转发"
                />
    
            @if($config['isWebhook'])
            <x-input.group for="webhookUrl" label="webhookUrl">
                <x-jet-input id="webhookUrl" type="text" class="mt-1 block w-full"  autocomplete="webhookUrl" 
                    wire:model="config.webhookUrl"
                    wire:blur.stop="$set('config.webhookUrl', $event.target.value)"
                    />
            </x-input.group>

            <x-input.group for="webhookSecret" label="webhookSecret">
                <x-jet-input id="webhookSecret" type="text" class="mt-1 block w-full"  autocomplete="webhookSecret" 
                    wire:model="config.webhookSecret"
                    wire:blur.stop="$set('config.webhookSecret', $event.target.value)"
                    />
            </x-input.group>

            <x-input.group for="webhookUrl2" label="webhookUrl2">
                <x-jet-input id="webhookUrl2" type="text" class="mt-1 block w-full"  autocomplete="webhookUrl2" 
                    wire:model="config.webhookUrl2"
                    wire:blur.stop="$set('config.webhookUrl2', $event.target.value)"
                    />
            </x-input.group>
            @endif
        </div>
        @endif

    </div>
</div>