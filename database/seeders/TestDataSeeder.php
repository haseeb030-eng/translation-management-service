<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
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
            ]
        ]
    ];

    public function run()
    {
        // Create test user if doesn't exist
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password123'),
            ]
        );

        // Create languages
        $languages = [
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'fr', 'name' => 'French'],
            ['code' => 'es', 'name' => 'Spanish'],
            ['code' => 'de', 'name' => 'German'],
            ['code' => 'it', 'name' => 'Italian'],
            ['code' => 'zh', 'name' => 'Chinese']
        ];

        foreach ($languages as $language) {
            Language::firstOrCreate(
                ['code' => $language['code']],
                ['name' => $language['name']]
            );
        }

        // Create tags
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

        foreach ($tags as $tagName) {
            Tag::firstOrCreate(['name' => $tagName]);
        }

        // Seed translations from sample values
        $this->seedTranslations();

        $this->command->info('Test data seeding completed!');
    }

    private function seedTranslations()
    {
        $languages = Language::all();
        $translations = [];

        foreach ($this->sampleValues as $section => $items) {
            foreach ($items as $key => $translations_set) {
                $translationKey = "{$section}.{$key}";

                foreach ($languages as $language) {
                    if (isset($translations_set[$language->code])) {
                        $translations[] = [
                            'language_id' => $language->id,
                            'key' => $translationKey,
                            'value' => $translations_set[$language->code],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }

        // Insert translations in chunks to avoid memory issues
        foreach (array_chunk($translations, 100) as $chunk) {
            DB::table('translations')->insertOrIgnore($chunk);
        }

        // Assign random tags to translations
        $this->assignRandomTags();
    }

    private function assignRandomTags()
    {
        $translations = Translation::all();
        $tags = Tag::all();
        $pivotData = [];

        foreach ($translations as $translation) {
            // Assign 1-3 random tags to each translation
            $tagCount = rand(1, 3);
            $selectedTags = $tags->random($tagCount);

            foreach ($selectedTags as $tag) {
                $pivotData[] = [
                    'translation_id' => $translation->id,
                    'tag_id' => $tag->id,
                ];
            }
        }

        // Insert tag relationships
        DB::table('translation_tag')->insertOrIgnore($pivotData);
    }
}
