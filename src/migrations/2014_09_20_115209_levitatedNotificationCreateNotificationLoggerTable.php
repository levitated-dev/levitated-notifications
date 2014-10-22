<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class LevitatedNotificationCreateNotificationLoggerTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'notificationsLog',
            function ($table) {
                /* @var Illuminate\Database\Schema\Builder $table */
                $table->increments('id');
                $table->enum(
                    'channel',
                    [
                        Levitated\Notifications\NotificationInterface::CHANNEL_EMAIL,
                        Levitated\Notifications\NotificationInterface::CHANNEL_SMS
                    ]
                );
                $table->string('recipientEmail');
                $table->string('recipientPhone');
                $table->longText('bodyPlain');
                $table->longText('bodyHtml');
                $table->text('subject');
                $table->text('params');
                $table->enum(
                    'state',
                    [
                        \Levitated\Notifications\NotificationSender::STATE_QUEUED,
                        \Levitated\Notifications\NotificationSender::STATE_SENDING,
                        \Levitated\Notifications\NotificationSender::STATE_SENT,
                        \Levitated\Notifications\NotificationSender::STATE_FAILED,
                        \Levitated\Notifications\NotificationSender::STATE_DROPPED,
                    ]
                )->default(\Levitated\Notifications\NotificationSender::STATE_QUEUED);
                $table->integer('numAttempts')->unsigned()->default(0);
                $table->text('errorMessage')->nullable();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('notificationsLog');
    }
}
