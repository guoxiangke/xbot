<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;


use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\BelongsTo;
use Lorisleiva\CronTranslator\CronTranslator;

class XbotSubscription extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\XbotSubscription::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
    ];

    // public static $with = ['user'];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [

            // $table->foreignId('user_id')->comment('谁来发');//发送的Bot即为本用户的bot：Bot拥有者或者叫bot
            // $table->foreignId('wechat_contact_id')->index()->comment('发给谁');//订阅联系人/群
            // $table->string('keyword')->comment('订阅的资源关键字');
            // $table->unsignedInteger('price')->default(0)->comment('实际订阅价格，单位分');
            // $table->foreignId('wechat_pay_order_id')->nullable();
            // //高级会员订阅,自定义发送时间
            // $table->string('cron')->default('0 7 * * *');
            
            ID::make(__('ID'), 'id')->sortable(),

            BelongsTo::make('who','WechatBot', 'App\Nova\WechatBot'), //谁的机器人
            BelongsTo::make('To','WechatBotContact', 'App\Nova\WechatBotContact')->searchable(),
            // BelongsTo::make('Author', 'author', 'App\Nova\User'),
            Text::make('keyword')
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('price')
                ->sortable()
                ->rules('required', 'max:255')
                ->hideFromIndex()
                ->default(0),
            Text::make('wechat_pay_order_id')
                ->sortable()
                ->rules('required', 'max:255')
                ->hideFromIndex()
                ->default(NULL),
            Text::make('cron', function () {
                return CronTranslator::translate($this->cron);
            })->showOnIndex(),
            Text::make('cron')
                ->sortable()
                ->rules('required', 'max:255')
                ->hideFromIndex(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
