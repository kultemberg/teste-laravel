<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */

    public function up(): void
    {
        Schema::create('cobrancas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $table->date('data_referencia');
            $table->date('data_vencimento');
            $table->decimal('valor', 12, 2);
            $table->decimal('valor_pago', 12, 2)->default(0);
            $table->decimal('valor_credito_aplicado', 12, 2)->default(0);
            $table->string('status');
            $table->text('motivo_cancelamento')->nullable();
            $table->timestamps();

            $table->unique(['contrato_id', 'data_referencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cobrancas');
    }
};