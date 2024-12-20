<?php

namespace YesWiki\YunoHost\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Importer\Service\ImporterManager;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\ListManager;
use YesWiki\Core\Service\PageManager;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Wiki;

class YunohostCLIAppImporter extends \YesWiki\Importer\Service\Importer
{
    protected $source;
    protected $databaseForms;
    protected $databaseLists;

    public function __construct(
        string $source,
        ParameterBagInterface $params,
        ContainerInterface $services,
        EntryManager $entryManager,
        ImporterManager $importerManager,
        FormManager $formManager,
        ListManager $listManager,
        Wiki $wiki
    ) {
        $this->source = $source;
        $this->params = $params;
        $this->services = $services;
        $this->entryManager = $entryManager;
        $this->importerManager = $importerManager;
        $this->formManager = $formManager;
        $this->listManager = $listManager;
        $this->wiki = $wiki;
        $config = $this->checkConfig($params->get('dataSources')[$this->source]);
        $this->config = $config;
        $this->databaseForms = [
            [
                "bn_id_nature" => null,
                "bn_label_nature" =>  "Applications Yunohost",
                "bn_description" =>  "Les applications disponibles dans le yunohost",
                "bn_condition" =>  "",
                "bn_sem_context" =>  "",
                "bn_sem_type" =>  "",
                "bn_sem_use_template" =>  "1",
                "bn_template" =>  <<<EOT
texte***bf_titre***Nom de l'application***255***255*** *** ***text***1*** *** *** * *** * *** *** *** ***
image***bf_image***Logo***400***400***1000***1000***right***0*** *** *** * *** * *** *** *** ***
textelong***bf_description***Description de l'application***80***12*** *** ***wiki***0*** *** *** * *** * *** *** *** ***
liste***ListeVisibilite***Visibilité de l'application*** *** *** *** *** ***1*** *** *** * *** * *** *** *** ***
texte***yunohost_app_id***Identifiant de l'application Yunohost***255***255*** *** *** ***0*** *** *** * *** * *** *** *** ***
lien_internet***bf_url***Url d'accès au service*** *** *** *** *** ***0*** *** *** * *** * *** *** *** ***
acls*** * ***@admins***comments-closed*** ***non*** ***0*** *** *** ***
EOT,
                "bn_ce_i18n" =>  "fr-FR",
                "bn_only_one_entry" =>  "N",
                "bn_only_one_entry_message" =>  null
            ]
        ];

        $this->databaseLists = [
            'ListeVisibilite' =>  [
                "titre_liste" => "Visibilité",
                "label" => [
                    "pub" => "Publique",
                    "priv" => "Privée"
                ]
            ]
        ];
    }

    /**
     * Check if config input is good enough to be used by Importer
     * @param array $config
     * @return array $config checked config
     */
    public function checkConfig(array $config)
    {
        $config = parent::checkConfig($config);
        return $config;
    }

    public function getData()
    {
        exec('sudo -n ' . getcwd() . '/tools/yunohost/private/scripts/yunohost-app-list.sh --full --output-as json', $output, $retval);

        if ($retval == 0) {
            $data = json_decode($output[0], true)['apps'] ?? null;
        } else {
            exit('yunohost-app-list.sh returned an error:' . "\n" . implode('<br>', $output) . "\n");
        }
        return $data ?? null;
    }

    public function mapData($data)
    {
        $preparedData = [];

        if (is_array($data) && !empty($data)) {
            foreach ($data as $i => $item) {
                if (!empty($item['domain_path'])) {
                    $preparedData[$i]['bf_titre'] = $item['name'];
                    $preparedData[$i]['yunohost_app_id'] = $item['settings']['app'];
                    $preparedData[$i]['bf_description'] = $item['manifest']['description'][$this->config['lang']] ?? $item['manifest']['description']['en'] ?? '';
                    if (!empty($item['permissions'][$item['settings']['app'] . '.main']['allowed'])) {
                        $preparedData[$i]['listeListeVisibilite'] = in_array('visitors', $item['permissions'][$item['settings']['app'] . '.main']['allowed']) ? 'pub' : 'priv';
                    } else {
                        $preparedData[$i]['listeListeVisibilite'] = 'priv';
                    }
                    $preparedData[$i]['imagebf_image'] = $this->importerManager->downloadFile('https://apps.yunohost.org/default/v3/logos/' . $item['logo'] . '.png');
                    $preparedData[$i]['bf_url'] = 'https://' . $item['domain_path'];
                }
            }
        } else {
            echo 'No datas found from source';
        }
        return $preparedData;
    }

    public function syncData($data)
    {
        $existingEntries = $this->entryManager->search(['formsIds' => [$this->config['formId']]]);
        $yunohostAppFunc = static function ($entry1, $entry2) {
            $value1 = isset($entry1['settings']) ? $entry1['settings']['app'] : $entry1['yunohost_app_id'];
            $value2 = isset($entry2['settings']) ? $entry2['settings']['app'] : $entry2['yunohost_app_id'];
            return $value1 <=> $value2;
        };
        $newYunohostApps = array_udiff($data, $existingEntries, $yunohostAppFunc);
        $removedYunohostApps = array_udiff($existingEntries, $data, $yunohostAppFunc);
        foreach ($newYunohostApps as $entry) {
            $entry['antispam'] = 1;
            try {
                $this->entryManager->create($this->config['formId'], $entry);
                echo 'La fiche de l\'application "' . $entry['bf_titre'] . '" a bien été créée.' . "\n";
            } catch (Exception $ex) {
                echo 'Erreur lors de la création de la fiche application ' . $entry['bf_titre'] . ' : ' . $ex->getMessage() . "\n";
            }
        }
        foreach ($removedYunohostApps as $entry) {
            try {
                // TODO use this when 4.5 is released
                // $this->entryManager->delete($existingEntries[$entry]['id_fiche']);
                $tag = $entry['id_fiche'];
                $fiche = $this->entryManager->getOne($tag, false, null, true);
                if (empty($fiche)) {
                    throw new Exception("Not existing entry : $tag");
                }
                $this->services->get(PageManager::class)->deleteOrphaned($tag);
                $this->services->get(TripleStore::class)->delete($tag, TripleStore::TYPE_URI, null, '', '');
                $this->services->get(TripleStore::class)->delete($tag, TripleStore::SOURCE_URL_URI, null, '', '');
                echo 'L\'application "' . $entry['bf_titre'] . '" a été supprimé.' . "\n";
            } catch (Exception $ex) {
                echo 'Erreur lors de la suppression de la fiche application ' . $entry['bf_titre'] . ' : ' . $ex->getMessage() . "\n";
            }
        }
        return;
    }

    public function syncFormModel()
    {
        // test if the lists exist, if not, install them
        foreach ($this->databaseLists as $tag => $list) {
            $liste = $this->listManager->getOne($tag);
            if (empty($liste)) {
                // TODO : comment etre sur de l'id ?
                $this->listManager->create($list['titre_liste'], $list['label']);
            } else {
                echo 'La liste "' . $list['titre_liste'] . '" existe deja.' . "\n";
                // test if compatible
            }
        }
        // test if the form exists, if not, install it
        $form = $this->formManager->getOne($this->config['formId']);
        if (empty($form)) {
            $this->databaseForms[0]['bn_id_nature'] = $this->config['formId'];
            $this->formManager->create($this->databaseForms[0]);
        } else {
            echo 'La base bazar existe deja.' . "\n";
            // test if compatible
        }
        return;
    }
}
