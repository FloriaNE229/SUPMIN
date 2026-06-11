<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Modules\Entities\Models\Entity;

class EntitySeeder extends Seeder
{
    public function run(): void
    {
        $entites = [
            // === ENTITÉS D'ORIGINE DES AGENTS (Directions centrales et techniques) ===
            ['code' => 'DNSP', 'sigle' => 'DNSP', 'denomination' => 'Direction nationale de la Santé publique', 'type_entite' => 'structure_administrative', 'region' => 'Littoral'],
            ['code' => 'DGMHED', 'sigle' => 'DGMHED', 'denomination' => 'Direction générale de la Médecine hospitalière et des Explorations diagnostiques', 'type_entite' => 'structure_administrative', 'region' => 'Littoral'],
            ['code' => 'DRFMT', 'sigle' => 'DRFMT', 'denomination' => 'Direction de la Recherche, de la Formation et de la Médecine traditionnelle', 'type_entite' => 'structure_administrative', 'region' => 'Littoral'],
            ['code' => 'DSI', 'sigle' => 'DSI', 'denomination' => 'Direction des Systèmes d\'Information', 'type_entite' => 'structure_administrative', 'region' => 'Littoral'],
            ['code' => 'DDS', 'sigle' => 'DDS', 'denomination' => 'Directions départementales de la Santé', 'type_entite' => 'structure_administrative', 'region' => 'National'],

            // === AGENCES ET ORGANISMES SOUS TUTELLE ===
            ['code' => 'ANSSP', 'sigle' => 'ANSSP', 'denomination' => 'Agence nationale des Soins de Santé primaires', 'type_entite' => 'agence', 'region' => 'Littoral'],
            ['code' => 'ANTS', 'sigle' => 'ANTS', 'denomination' => 'Agence nationale pour la Transfusion sanguine', 'type_entite' => 'agence', 'region' => 'Littoral'],
            ['code' => 'ANCQ', 'sigle' => 'ANCQ', 'denomination' => 'Agence nationale de Contrôle de Qualité des Produits de Santé et de l\'Eau', 'type_entite' => 'agence', 'region' => 'Atlantique'],
            ['code' => 'ABRP', 'sigle' => 'ABRP', 'denomination' => 'Agence béninoise de régulation pharmaceutique', 'type_entite' => 'agence', 'region' => 'Littoral'],
            ['code' => 'AISEM', 'sigle' => 'AISEM', 'denomination' => 'Agence des Infrastructures sanitaires, des Équipements et de la Maintenance', 'type_entite' => 'agence', 'region' => 'Ouémé'],
            ['code' => 'SAMU', 'sigle' => 'SAMU', 'denomination' => 'Service d\'Aide médicale d\'Urgence', 'type_entite' => 'agence', 'region' => 'Littoral'],

            // === ÉTABLISSEMENTS HOSPITALIERS ===
            ['code' => 'CHU', 'sigle' => 'CHU', 'denomination' => 'Centres hospitaliers universitaires', 'type_entite' => 'structure_administrative', 'region' => 'Littoral'],

            // === STRUCTURES SPÉCIALISÉES ===
            ['code' => 'CPMINFED', 'sigle' => 'CPMI-NFED', 'denomination' => 'Centre de Prise en charge médicale intégrée du Nourrisson et de la Femme enceinte atteints de Drépanocytose', 'type_entite' => 'structure_administrative', 'region' => 'Littoral'],

            // === ORGANES CONSULTATIFS ET DE RÉGULATION ===
            ['code' => 'CNSSP', 'sigle' => 'CNSSP', 'denomination' => 'Conseil national des Soins de Santé primaires', 'type_entite' => 'structure_administrative', 'region' => 'Littoral'],
            ['code' => 'CNMH', 'sigle' => 'CNMH', 'denomination' => 'Conseil national de la Médecine hospitalière', 'type_entite' => 'structure_administrative', 'region' => 'Littoral'],
            ['code' => 'CNERS', 'sigle' => 'CNERS', 'denomination' => 'Comité national d\'Éthique pour la Recherche en Santé', 'type_entite' => 'structure_administrative', 'region' => 'Littoral'],
            ['code' => 'OPS', 'sigle' => 'OPS', 'denomination' => 'Ordres des professionnels de la santé', 'type_entite' => 'structure_administrative', 'region' => 'Littoral'],

            // === PROGRAMMES DE SANTÉ ===
            ['code' => 'PNLP', 'sigle' => 'PNLP', 'denomination' => 'Programme National de Lutte contre le Paludisme', 'type_entite' => 'programme', 'region' => 'National'],
            ['code' => 'PNT', 'sigle' => 'PNT', 'denomination' => 'Programme de Lutte contre la Tuberculose', 'type_entite' => 'programme', 'region' => 'National'],
            ['code' => 'PEV', 'sigle' => 'PEV', 'denomination' => 'Programme Élargi de Vaccination', 'type_entite' => 'programme', 'region' => 'National'],
            ['code' => 'PNLS', 'sigle' => 'PNLS', 'denomination' => 'Programme National de Lutte contre le Sida', 'type_entite' => 'programme', 'region' => 'National'],
            ['code' => 'PNSC', 'sigle' => 'PNSC', 'denomination' => 'Politique Nationale de Santé Communautaire', 'type_entite' => 'programme', 'region' => 'National'],
            ['code' => 'PSILMNT', 'sigle' => 'PSILMNT', 'denomination' => 'Programme National de Lutte contre les Maladies Non Transmissibles', 'type_entite' => 'programme', 'region' => 'National'],
        ];

        foreach ($entites as $data) {
            Entity::firstOrCreate(
                ['code' => $data['code']],
                [
                    'id'              => (string) Str::uuid(),
                    'denomination'    => $data['denomination'],
                    'sigle'           => $data['sigle'],
                    'type_entite'     => $data['type_entite'],
                    'localisation'    => $data['region'],
                    'region'          => $data['region'],
                    'responsable_id'  => null,
                    'statut'          => 'actif',
                    'date_creation'   => now(),
                ]
            );
        }
    }
}