<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Language;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Support\Facades\DB;

class GenerateTranslationData extends Command
{
    protected $signature = 'translations:generate {count=100000}';
    protected $description = 'Generate test translation data';

    private $sampleValues = [
        'common' => [
            'save' => [
                'en' => 'Save changes',
                'fr' => 'Enregistrer les modifications',
                'es' => 'Guardar cambios',
                'de' => 'Änderungen speichern',
                'it' => 'Salva modifiche',
                'zh' => '保存更改'
            ],
            'cancel' => [
                'en' => 'Cancel',
                'fr' => 'Annuler',
                'es' => 'Cancelar',
                'de' => 'Abbrechen',
                'it' => 'Annulla',
                'zh' => '取消'
            ],
            'delete' => [
                'en' => 'Delete',
                'fr' => 'Supprimer',
                'es' => 'Eliminar',
                'de' => 'Löschen',
                'it' => 'Elimina',
                'zh' => '删除'
            ],
            'confirm' => [
                'en' => 'Are you sure?',
                'fr' => 'Êtes-vous sûr ?',
                'es' => '¿Está seguro?',
                'de' => 'Sind Sie sicher?',
                'it' => 'Sei sicuro?',
                'zh' => '您确定吗？'
            ],
            'yes' => [
                'en' => 'Yes',
                'fr' => 'Oui',
                'es' => 'Sí',
                'de' => 'Ja',
                'it' => 'Sì',
                'zh' => '是'
            ],
            'no' => [
                'en' => 'No',
                'fr' => 'Non',
                'es' => 'No',
                'de' => 'Nein',
                'it' => 'No',
                'zh' => '否'
            ],
            'success' => [
                'en' => 'Success!',
                'fr' => 'Succès !',
                'es' => '¡Éxito!',
                'de' => 'Erfolg!',
                'it' => 'Successo!',
                'zh' => '成功！'
            ],
            'error' => [
                'en' => 'Error occurred',
                'fr' => 'Une erreur est survenue',
                'es' => 'Ha ocurrido un error',
                'de' => 'Fehler aufgetreten',
                'it' => 'Si è verificato un errore',
                'zh' => '发生错误'
            ]
        ],
        'auth' => [
            'signin' => [
                'en' => 'Please sign in',
                'fr' => 'Veuillez vous connecter',
                'es' => 'Por favor, inicie sesión',
                'de' => 'Bitte anmelden',
                'it' => 'Accedi',
                'zh' => '请登录'
            ],
            'forgot_password' => [
                'en' => 'Forgot password?',
                'fr' => 'Mot de passe oublié ?',
                'es' => '¿Olvidó su contraseña?',
                'de' => 'Passwort vergessen?',
                'it' => 'Password dimenticata?',
                'zh' => '忘记密码？'
            ],
            'register' => [
                'en' => 'Register now',
                'fr' => 'Inscrivez-vous maintenant',
                'es' => 'Regístrese ahora',
                'de' => 'Jetzt registrieren',
                'it' => 'Registrati ora',
                'zh' => '立即注册'
            ],
            'invalid_credentials' => [
                'en' => 'Invalid credentials',
                'fr' => 'Identifiants invalides',
                'es' => 'Credenciales inválidas',
                'de' => 'Ungültige Anmeldedaten',
                'it' => 'Credenziali non valide',
                'zh' => '无效的凭据'
            ],
            'welcome_back' => [
                'en' => 'Welcome back',
                'fr' => 'Bon retour',
                'es' => 'Bienvenido de nuevo',
                'de' => 'Willkommen zurück',
                'it' => 'Bentornato',
                'zh' => '欢迎回来'
            ]
        ],
        'errors' => [
            'not_found' => [
                'en' => 'Page not found',
                'fr' => 'Page non trouvée',
                'es' => 'Página no encontrada',
                'de' => 'Seite nicht gefunden',
                'it' => 'Pagina non trovata',
                'zh' => '找不到页面'
            ],
            'server_error' => [
                'en' => 'Server error',
                'fr' => 'Erreur serveur',
                'es' => 'Error del servidor',
                'de' => 'Serverfehler',
                'it' => 'Errore del server',
                'zh' => '服务器错误'
            ],
            'access_denied' => [
                'en' => 'Access denied',
                'fr' => 'Accès refusé',
                'es' => 'Acceso denegado',
                'de' => 'Zugriff verweigert',
                'it' => 'Accesso negato',
                'zh' => '访问被拒绝'
            ],
            'invalid_input' => [
                'en' => 'Invalid input',
                'fr' => 'Entrée invalide',
                'es' => 'Entrada inválida',
                'de' => 'Ungültige Eingabe',
                'it' => 'Input non valido',
                'zh' => '输入无效'
            ],
            'try_again' => [
                'en' => 'Please try again',
                'fr' => 'Veuillez réessayer',
                'es' => 'Por favor, inténtelo de nuevo',
                'de' => 'Bitte versuchen Sie es erneut',
                'it' => 'Per favore riprova',
                'zh' => '请重试'
            ]
        ]
    ];

    public function handle()
    {
        $count = (int) $this->argument('count');

        if ($count < 1000) {
            $this->error('Please generate at least 1,000 records for meaningful testing');
            return 1;
        }

        // Check if translations already exist
        if (Translation::count() > 0) {
            if (!$this->confirm('There are existing translations. Do you want to proceed and add more?')) {
                return 1;
            }
        }

        $this->info("Generating {$count} translation records...");

        // Ensure we have languages
        $languages = Language::all();
        if ($languages->isEmpty()) {
            $this->info('Creating default languages...');
            $languages = $this->createDefaultLanguages();
        }

        // Ensure we have tags
        $tags = Tag::all();
        if ($tags->isEmpty()) {
            $this->info('Creating default tags...');
            $tags = $this->createDefaultTags();
        }

        // Generate translations in batches to avoid memory issues
        $batchSize = 1000;
        $batches = ceil($count / $batchSize);

        $keyPrefixes = array_keys($this->sampleValues);

        $bar = $this->output->createProgressBar($count * count($languages));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->start();

        // Keep track of used keys to prevent duplicates
        $usedKeys = [];
        $usedTranslationSets = [];

        // Create translations in batches
        for ($batch = 0; $batch < $batches; $batch++) {
            $translations = [];

            for ($i = 0; $i < $batchSize; $i++) {
                $recordIndex = ($batch * $batchSize) + $i;

                if ($recordIndex >= $count) {
                    break;
                }

                // Generate a unique key and select a translation set
                do {
                    $prefix = $keyPrefixes[array_rand($keyPrefixes)];
                    $section = 'section_' . rand(1, 20);
                    $key = $prefix . '.' . $section . '.item_' . rand(1, 100);
                } while (isset($usedKeys[$key]));

                $usedKeys[$key] = true;

                // Select a random translation set from the prefix
                $translationSets = $this->sampleValues[$prefix];
                $translationKey = array_rand($translationSets);
                $translationSet = $translationSets[$translationKey];

                foreach ($languages as $language) {
                    // Use the corresponding translation for each language
                    $value = $translationSet[$language->code] ?? $translationSet['en'];

                    $translations[] = [
                        'language_id' => $language->id,
                        'key' => $key,
                        'value' => $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $bar->advance();
                }
            }

            // Insert batch with duplicate handling
            foreach (array_chunk($translations, 100) as $chunk) {
                DB::table('translations')->insertOrIgnore($chunk);
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Assign tags to some translations
        $this->info('Assigning tags to translations...');

        // Get a sample of translations to tag (20% of total)
        $sampleSize = min(20000, (int)($count * 0.2));
        $translationIds = Translation::inRandomOrder()->limit($sampleSize)->pluck('id')->toArray();
        $tagIds = $tags->pluck('id')->toArray();

        $chunks = array_chunk($translationIds, 1000);
        $bar = $this->output->createProgressBar(count($chunks));
        $bar->start();

        foreach ($chunks as $chunk) {
            $pivotData = [];

            foreach ($chunk as $translationId) {
                // Assign 1-3 random tags
                $tagCount = rand(1, 3);
                $selectedTagIds = array_rand(array_flip($tagIds), $tagCount);

                if (!is_array($selectedTagIds)) {
                    $selectedTagIds = [$selectedTagIds];
                }

                foreach ($selectedTagIds as $tagId) {
                    $pivotData[] = [
                        'translation_id' => $translationId,
                        'tag_id' => $tagId,
                    ];
                }
            }

            DB::table('translation_tag')->insertOrIgnore($pivotData);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Successfully generated translation data!');

        return 0;
    }

    /**
     * Create default languages if none exist
     */
    private function createDefaultLanguages()
    {
        $languages = [
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'fr', 'name' => 'French'],
            ['code' => 'es', 'name' => 'Spanish'],
            ['code' => 'de', 'name' => 'German'],
            ['code' => 'it', 'name' => 'Italian'],
            ['code' => 'zh', 'name' => 'Chinese'],
        ];

        $created = [];

        foreach ($languages as $language) {
            $created[] = Language::firstOrCreate(
                ['code' => $language['code']],
                $language
            );
        }

        return collect($created);
    }

    /**
     * Create default tags if none exist
     */
    private function createDefaultTags()
    {
        $tags = [
            'mobile',
            'desktop',
            'web',
            'admin',
            'user',
            'error',
            'success',
            'notification',
            'button',
            'form',
        ];

        $created = [];

        foreach ($tags as $tagName) {
            $created[] = Tag::firstOrCreate(['name' => $tagName]);
        }

        return collect($created);
    }
}
