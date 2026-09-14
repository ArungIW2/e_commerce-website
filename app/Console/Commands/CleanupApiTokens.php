<?php
namespace App\Console\Commands;
use App\Models\ApiToken;
use Illuminate\Console\Command;
class CleanupApiTokens extends Command {
    protected $signature='api-tokens:cleanup {--days=7 : Keep revoked/expired tokens for this many days}';
    protected $description='Delete old revoked and expired API tokens';
    public function handle():int {
        $days=max(0,(int)$this->option('days')); $cutoff=now()->subDays($days);
        $deleted=ApiToken::query()->where(function($query)use($cutoff){$query->whereNotNull('revoked_at')->where('revoked_at','<',$cutoff)->orWhere(function($query)use($cutoff){$query->whereNull('revoked_at')->whereNotNull('expires_at')->where('expires_at','<',$cutoff);});})->delete();
        $this->info("Deleted {$deleted} stale API tokens."); return self::SUCCESS;
    }
}
