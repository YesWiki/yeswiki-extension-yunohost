<?php

namespace YesWiki\YunoHost\Service;

use Exception;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Importer\Service\ImporterManager;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\ListManager;
use YesWiki\Wiki;

class YunohostCLIUserImporter extends \YesWiki\Importer\Service\Importer
{
    protected $databaseForms;

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
            ["bn_id_nature" => null,
                "bn_label_nature" =>  "Utilisateurs Yunohost",
                "bn_description" =>  "Les utilisateurs disponibles dans le yunohost",
                "bn_condition" =>  "",
                "bn_sem_context" =>  "",
                "bn_sem_type" =>  "",
                "bn_sem_use_template" =>  "1",
                "bn_template" => <<<EOT
texte***bf_titre***Nom d'utilisateur*** *** *** *** ***text***1*** *** *** * *** * *** *** *** ***
texte***bf_nom***Nom complet*** *** *** *** ***text***0*** *** *** * *** * *** *** *** ***
champs_mail***bf_mail***Email*** *** % *** ***form*** ***0***0*** *** * *** % *** *** *** ***
texte***bf_quota***Quota mail*** *** *** *** ***number***0*** *** *** * *** * *** *** *** ***
EOT,
                "bn_ce_i18n" =>  "fr-FR",
                "bn_only_one_entry" =>  "N",
                "bn_only_one_entry_message" =>  null
            ]
        ];
    }

    public function getData()
    {
        exec('sudo -n '.getcwd().'/tools/yunohost/private/scripts/yunohost-user-list.sh', $output, $retval);

        if ($retval == 0) {
            $data = json_decode($output[0], true)['users'] ?? null;
        } else {
            exit('yunohost-user-list.sh returned an error:'."\n".implode('<br>', $output)."\n");
        }

        return $data;
    }

    public function mapData($data)
    {
        $preparedData = [];
        if (is_array($data) && !empty($data)) {
            foreach ($data as $i => $item) {
                $preparedData[$i]['bf_titre'] = $item['username'];
                $preparedData[$i]['bf_nom'] = $item['fullname'];
                $preparedData[$i]['bf_mail'] = $item['mail'];
                $preparedData[$i]['bf_quota'] = $item['mailbox-quota'];
            }
        } else {
            echo 'No datas found from source';
        }
        return $preparedData;
    }

    public function syncFormModel()
    {
        // test if the form exists, if not, install it
        $form = $this->formManager->getOne($this->config['formId']);
        if (empty($form)) {
            $this->databaseForms[0]['bn_id_nature'] = $this->config['formId'];
            $this->formManager->create($this->databaseForms[0]);
        } else {
            echo 'La base bazar existe deja.'."\n";
            // test if compatible
        }
        return;
    }

    public function syncData(array $data)
    {
        $existingEntries = $this->entryManager->search(['formsIds' => [$this->config['formId']]]);
        $newYunohostUsers = array_udiff(array_keys($data), array_keys($existingEntries), 'strcasecmp');
        $removedYunohostUsers = array_udiff(array_keys($existingEntries), array_keys($data), 'strcasecmp');
        foreach ($newYunohostUsers as $entry) {
            $data[$entry]['antispam'] = 1;
            try {
                $this->entryManager->create($this->config['formId'], $data[$entry]);
                echo 'L\'utilisateur "'.$data[$entry]['bf_titre'].'" créé.'."\n";
            } catch (Exception $ex) {
                echo 'Erreur lors de la création de la fiche utilisateur '.$data[$entry]['bf_titre'].' : '.$ex->getMessage()."\n";
            }
        }
        foreach ($removedYunohostUsers as $entry) {
            try {
                $this->entryManager->delete($existingEntries[$entry]['id_fiche']);
                echo 'L\'utilisateur "'.$existingEntries[$entry]['bf_titre'].'" a été supprimé.'."\n";
            } catch (Exception $ex) {
                echo 'Erreur lors de la suppression de la fiche utilisateur '.$existingEntries[$entry]['bf_titre'].' : '.$ex->getMessage()."\n";
            }
        }
        return;
    }
}
