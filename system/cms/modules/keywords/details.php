<?php defined('BASEPATH') or exit('No direct script access allowed');

use Capsule\Schema;

/**
 * Keywords module
 *
 * @author PyroCMS Dev Team
 * @package PyroCMS\Core\Modules\Keywords
 */
class Module_Keywords extends Module
{
    public $version = '1.1.0';

    public $_tables = array('keywords', 'keywords_applied');

    public function info()
    {
        return array(
            'name' => array(
                'en' => 'Keywords',
                'ar' => 'كلمات البحث',
                'br' => 'Palavras-chave',
                'pt' => 'Palavras-chave',
                'da' => 'Nøgleord',
                'el' => 'Λέξεις Κλειδιά',
                'fr' => 'Mots-Clés',
                'id' => 'Kata Kunci',
                'nl' => 'Sleutelwoorden',
                'zh' => '鍵詞',
                'hu' => 'Kulcsszavak',
                'fi' => 'Avainsanat',
                'sl' => 'Ključne besede',
                'th' => 'คำค้น',
                'se' => 'Nyckelord',
            ),
            'description' => array(
                'en' => 'Maintain a central list of keywords to label and organize your content.',
                'ar' => 'أنشئ مجموعة من كلمات البحث التي تستطيع من خلالها وسم وتنظيم المحتوى.',
                'br' => 'Mantém uma lista central de palavras-chave para rotular e organizar o seu conteúdo.',
                'pt' => 'Mantém uma lista central de palavras-chave para rotular e organizar o seu conteúdo.',
                'da' => 'Vedligehold en central liste af nøgleord for at organisere dit indhold.',
                'el' => 'Συντηρεί μια κεντρική λίστα από λέξεις κλειδιά για να οργανώνετε μέσω ετικετών το περιεχόμενό σας.',
                'fr' => 'Maintenir une liste centralisée de Mots-Clés pour libeller et organiser vos contenus.',
                'id' => 'Memantau daftar kata kunci untuk melabeli dan mengorganisasikan konten.',
                'nl' => 'Beheer een centrale lijst van sleutelwoorden om uw content te categoriseren en organiseren.',
                'zh' => '集中管理可用於標題與內容的鍵詞(keywords)列表。',
                'hu' => 'Ez egy központi kulcsszó lista a cimkékhez és a tartalmakhoz.',
                'fi' => 'Hallinnoi keskitettyä listaa avainsanoista merkitäksesi ja järjestelläksesi sisältöä.',
                'sl' => 'Vzdržuj centralni seznam ključnih besed za označevanje in ogranizacijo vsebine.',
                'th' => 'ศูนย์กลางการปรับปรุงคำค้นในการติดฉลากและจัดระเบียบเนื้อหาของคุณ',
                'se' => 'Hantera nyckelord för att organisera webbplatsens innehåll.',
            ),
            'frontend' => false,
            'backend'  => true,
            'menu'     => 'data',
            'shortcuts' => array(
                array(
                   'name' => 'keywords:add_title',
                   'uri' => 'admin/keywords/add',
                   'class' => 'add',
                ),
            ),
        );
    }

    public function install()
    {
        Schema::dropIfExists('keywords');

        Schema::create('keywords', function($table) {
            $table->increments('id');
            $table->string('name', 50);
        });

        Schema::dropIfExists('keywords_applied');

        Schema::create('keywords_applied', function($table) {
            $table->increments('id');
            $table->string('hash', 32)->default('');
            $table->integer('keyword_id');

            // $table->foreign('keyword_id')->references('id')->on('keywords');
        });

        return true;
    }

    public function uninstall()
    {
        // This is a core module, lets keep it around.
        return false;
    }

    public function upgrade($old_version)
    {
        return true;
    }

}
