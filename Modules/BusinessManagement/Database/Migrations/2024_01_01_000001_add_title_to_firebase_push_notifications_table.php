<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTitleToFirebasePushNotificationsTable extends Migration
{
    public function up()
    {
        Schema::table('firebase_push_notifications', function (Blueprint $table) {
            $table->string('title', 191)->nullable()->after('name');
        });
    }

    public function down()
    {
        Schema::table('firebase_push_notifications', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
}
