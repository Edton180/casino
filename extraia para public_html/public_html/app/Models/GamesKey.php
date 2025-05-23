<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class GamesKey extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'games_keys';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'merchant_url',
        'merchant_id',
        'merchant_key',

        /// venix
        'venix_agent_code',
        'venix_agent_token',
        'venix_agent_secret',

        /// playigaming
        'pig_agent_code',
        'pig_agent_token',
        'pig_agent_secret',

        /// Play gaming
        'play_gaming_hall',
        'play_gaming_key',
        'play_gaming_login',


        /// Games2 Api
        'games2_agent_code',
        'games2_agent_token',
        'games2_agent_secret_key',
        'games2_api_endpoint',

        /// EverGame
        'evergame_agent_code',
        'evergame_agent_token',
        'evergame_api_endpoint',

        /// WorldSlot
        'worldslot_agent_code',
        'worldslot_agent_token',
        'worldslot_agent_secret_key',
        'worldslot_api_endpoint',

        /// Fivers
        'agent_code',
        'agent_token',
        'agent_secret_key',
        'api_endpoint',

        // Salsa
        'salsa_base_uri',
        'salsa_pn',
        'salsa_key',

        // PlayConnect
        'playconnect_code',
        'playconnect_token',
        'playconnect_secret_key',
        // Vibra
        'vibra_site_id',
        'vibra_game_mode',
        
      'gitslotpark_pg_agent',
      'gitslotpark_pg_token',
      'gitslotpark_pg_secret',
      'gitslotpark_pg_endpoint',
      'gitslotpark_pp_agent',
      'gitslotpark_pp_token',
      'gitslotpark_pp_secret',
      'gitslotpark_pp_endpoint',
      
              //SystemServer
        'system_url',
        'system_secret',
        'system_code',
        'system_token',
        
        //Center Gaming Pragmatic
        'apipragmatic40_url',
        'apipragmatic40_secret',
        'apipragmatic40_code',
        'apipragmatic40_token',
        
         // ADICIONE ESSAS LINHAS DE CÓDIGO
        // Play Fiver
        'playfiver_secret',
        'playfiver_code',
        'playfiver_token',
        
         //API PG 12
        'apipg12_url',
        'apipg12_secret',
        'apipg12_code',
        'apipg12_token',
    ];

    protected $hidden = array('updated_at');

    /**
     * Get the user's first name.
     */
    protected function pigAgentCode(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function pigAgentToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function pigAgentSecret(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }


    /**
     * Get the user's first name.
     */
    protected function merchantId(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function merchantKey(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function agentToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function agentCode(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function agentSecretKey(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function salsaBaseUri(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function salsaPn(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }


    /**
     * Get the user's first name.
     */
    protected function salsaKey(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function worldslotAgentCode(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function worldslotAgentToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function worldslotAgentSecretKey(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * GAMES 2 API *********************************************************
     * *********************************************************************
     */

    /**
     * Get the user's first name.
     */
    protected function games2ApiEndpoint(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function games2AgentToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function games2AgentSecretKey(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function venixAgentCode(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function venixAgentToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function venixAgentSecret(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function evergameAgentCode(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Get the user's first name.
     */
    protected function evergameAgentToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

}
