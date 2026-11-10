<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * New workspaces start in the brand's deep blue. Existing workspaces keep
     * the colour they have, since an office may have picked it on purpose.
     */
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->string('brand_color', 7)->default('#1E3A4C')->change();
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->string('brand_color', 7)->default('#145A55')->change();
        });
    }
};
