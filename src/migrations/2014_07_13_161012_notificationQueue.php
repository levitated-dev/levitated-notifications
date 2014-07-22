<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NotificationQueue extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('notifications', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('subject', 512);
            $table->string('to', 60);
            $table->string('fromName', 30);
            $table->string('replyTo', 30);
            $table->timestamp('sentAt');
            $table->enum('state', array('queued','processing','sent','failed','skipped'))->default('queued');
            $table->tinyInteger('tryNo');
            $table->timestamp('nextRetryAt')->nullable();
            $table->timestamp('toBeSentAt');
            $table->timestamp('createdAt');
            $table->timestamp('updatedAt');
            $table->timestamp('deletedAt')->nullable()->default(null);
            $table->longText('bodyHtml');
            $table->longText('bodyPlain');
            $table->enum('type', array('email', 'sms'))->default('email');
            $table->enum('relatedObjectType', array('unknown'))->default('email');
            $table->integer('relatedObjectId');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('notificationQueue');
	}
}
