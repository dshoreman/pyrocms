<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Addons Module
 *
 * @author PyroCMS Dev Team
 * @package PyroCMS\Core\Modules\Modules
 */
class Module_Addons extends Module
{
	public $version = '2.0.0';

	public function info()
	{
		$info = array(
			'name' => array(
				'en' => 'Add-ons',
				'ar' => 'الإضافات',
				'br' => 'Complementos',
				'pt' => 'Complementos',
				'cs' => 'Doplňky',
				'da' => 'Add-ons',
				'de' => 'Erweiterungen',
				'el' => 'Πρόσθετα',
				'es' => 'Agregados',
				'fi' => 'Lisäosat',
				'fr' => 'Extensions',
				'he' => 'תוספות',
				'id' => 'Pengaya',
				'it' => 'Add-ons',
				'lt' => 'Priedai',
				'nl' => 'Add-ons',
				'pl' => 'Rozszerzenia',
				'ru' => 'Дополнения',
				'sl' => 'Razširitve',
				'tw' => '附加模組',
				'cn' => '附加模组',
				'hu' => 'Bővítmények',
				'th' => 'ส่วนเสริม',
				'se' => 'Tillägg',
			),
			'description' => array(
				'en' => 'Allows admins to see a list of currently installed modules.',
				'ar' => 'تُمكّن المُدراء من معاينة جميع الوحدات المُثبّتة.',
				'br' => 'Permite aos administradores ver a lista dos módulos instalados atualmente.',
				'pt' => 'Permite aos administradores ver a lista dos módulos instalados atualmente.',
				'cs' => 'Umožňuje administrátorům vidět seznam nainstalovaných modulů.',
				'da' => 'Lader administratorer se en liste over de installerede moduler.',
				'de' => 'Zeigt Administratoren alle aktuell installierten Module.',
				'el' => 'Επιτρέπει στους διαχειριστές να προβάλουν μια λίστα των εγκατεστημένων πρόσθετων.',
				'es' => 'Permite a los administradores ver una lista de los módulos instalados.',
				'fi' => 'Listaa järjestelmänvalvojalle käytössä olevat moduulit.',
				'fr' => 'Permet aux administrateurs de voir la liste des modules installés',
				'he' => 'נותן אופציה למנהל לראות רשימה של המודולים אשר מותקנים כעת באתר או להתקין מודולים נוספים',
				'id' => 'Memperlihatkan kepada admin daftar modul yang terinstall.',
				'it' => 'Permette agli amministratori di vedere una lista dei moduli attualmente installati.',
				'lt' => 'Vartotojai ir svečiai gali komentuoti jūsų naujienas, puslapius ar foto.',
				'nl' => 'Stelt admins in staat om een overzicht van geinstalleerde modules te genereren.',
				'pl' => 'Umożliwiają administratorowi wgląd do listy obecnie zainstalowanych modułów.',
				'ru' => 'Список модулей, которые установлены на сайте.',
				'sl' => 'Dovoljuje administratorjem pregled trenutno nameščenih modulov.',
				'tw' => '管理員可以檢視目前已經安裝模組的列表',
				'cn' => '管理员可以检视目前已经安装模组的列表',
				'hu' => 'Lehetővé teszi az adminoknak, hogy lássák a telepített modulok listáját.',
				'th' => 'ช่วยให้ผู้ดูแลระบบดูรายการของโมดูลที่ติดตั้งในปัจจุบัน',
				'se' => 'Gör det möjligt för administratören att se installerade mouler.',
			),
			'frontend' => false,
			'backend' => true,
			'menu' => false,

			'sections' => array(
				'modules' => array(
					'name' => 'addons:modules',
					'uri' => 'admin/addons/modules',
				),
				'themes' => array(
					'name' => 'global:themes',
					'uri' => 'admin/addons/themes',
				),
				'plugins' => array(
					'name' => 'global:plugins',
					'uri' => 'admin/addons/plugins',
				),
				'widgets' => array(
					'name' => 'global:widgets',
					'uri' => 'admin/addons/widgets',
				),
				'field_types' => array(
					'name' => 'global:field_types',
					'uri' => 'admin/addons/field-types',
				),
			),
		);
	
		// Add upload options to various modules
		if ( ! class_exists('Module_import') and Settings::get('addons_upload'))
		{
			$info['sections']['modules']['shortcuts'] = array(
				array(
					'name' => 'global:upload',
					'uri' => 'admin/addons/modules/upload',
					'class' => 'add',
				),
			);

			$info['sections']['themes']['shortcuts'] = array(
				array(
					'name' => 'global:upload',
					'uri' => 'admin/addons/themes/upload',
					'class' => 'add modal',
				),
			);
		}

		return $info;
	}

	public function admin_menu(&$menu)
	{
		$menu['lang:cp:nav_addons'] = array(
			'lang:cp:nav_modules'			=> 'admin/addons',
			'lang:global:themes'			=> 'admin/addons/themes',
			'lang:global:plugins'			=> 'admin/addons/plugins',
			'lang:global:widgets'			=> 'admin/addons/widgets',
			'lang:global:field_types'		=> 'admin/addons/field-types'
		);

		add_admin_menu_place('lang:cp:nav_addons', 6);
	}

	public function install()
	{
		$schema = $this->pdb->getSchemaBuilder();

        $schema->dropIfExists('theme_options');

        $schema->create('theme_options', function($table) { 
            $table->increments('id');
            $table->string('slug', 30);
            $table->string('title', 100);
            $table->text('description');
            $table->enum('type', array('text', 'textarea', 'password', 'select', 'select-multiple', 'radio', 'checkbox', 'colour-picker'));
            $table->string('default', 255);
            $table->string('value', 255);
            $table->text('options');
            $table->boolean('is_required');
            $table->string('theme', 50);
        });

        $this->pdb->table('settings')->insert(array(
            array(
                'slug' => 'addons_upload',
                'title' => 'Addons Upload Permissions',
                'description' => 'Keeps mere admins from uploading addons by default',
                'type' => 'text',
                'default' => '0',
                'value' => '0',
                'options' => '',
                'is_required' => 1,
                'is_gui' => 0,
                'module' => '',
                'order' => 0,
            ),
            array(
                'slug' => 'default_theme',
                'title' => 'Default Theme',
                'description' => 'Select the theme you want users to see by default.',
                'type' => '',
                'default' => 'default',
                'value' => 'default',
                'options' => 'func:get_themes',
                'is_required' => 1,
                'is_gui' => 0,
                'module' => '',
                'order' => 0,
            ),
            array(
                'slug' => 'admin_theme',
                'title' => 'Control Panel Theme',
                'description' => 'Select the theme for the control panel.',
                'type' => '',
                'default' => '',
                'value' => 'pyrocms',
                'options' => 'func:get_themes',
                'is_required' => 1,
                'is_gui' => 0,
                'module' => '',
                'order' => 0,
            ),
        ));

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
