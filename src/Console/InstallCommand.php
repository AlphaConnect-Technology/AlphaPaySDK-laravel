<?php

declare(strict_types=1);

namespace AlphaPay\Laravel\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'alphapay:install';

    protected $description = 'Installe le package AlphaPay pour Laravel';

    public function handle(): int
    {
        $this->info('Installation d\'AlphaPay pour Laravel...');

        $this->call('vendor:publish', ['--tag' => 'alphapay-config']);

        if ($this->confirm('Voulez-vous publier et exécuter la migration des transactions ?', true)) {
            $this->call('vendor:publish', ['--tag' => 'alphapay-migrations']);
            $this->call('migrate');
        }

        $this->newLine();
        $this->info('✅ AlphaPay installé.');
        $this->newLine();

        $this->line('📋 Prochaines étapes :');
        $this->line('   1. Ajoutez votre clé secrète dans le fichier .env :');
        $this->line('      ALPHAPAY_SECRET_KEY=sk_test_...   (sk_live_... en production)');
        $this->newLine();
        $this->line('   2. Créez un endpoint webhook depuis le dashboard AlphaPay, avec cette URL :');
        $this->line('      ' . url(config('alphapay.webhook_path', 'webhooks/alphapay')));
        $this->newLine();
        $this->line('   3. Copiez le secret affiché (UNE SEULE FOIS, à la création) dans .env :');
        $this->line('      ALPHAPAY_WEBHOOK_SECRET=whsec_...');
        $this->line('      (ce secret est généré par AlphaPay, jamais localement -- rien à faire de plus ici)');
        $this->newLine();
        $this->line('📚 Documentation SDK : https://github.com/AlphaConnect-Technology/AlphaPaySDK-php');

        return self::SUCCESS;
    }
}
