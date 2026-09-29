<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';

$module=dirname(__DIR__);$controller=(string)file_get_contents($module.'/src/Controller/AdminExpenseController.php');$overview=(string)file_get_contents($module.'/src/Templates/Expenses/Content.php');$create=(string)file_get_contents($module.'/src/Templates/Create/Index.php');$editor=(string)file_get_contents($module.'/src/Templates/Editor/Content.php');$script=(string)file_get_contents($module.'/src/Templates/Expenses/Js/Expenses.js');$style=(string)file_get_contents($module.'/src/Templates/Editor/Css/Editor.scss');
$categoryForm=(string)file_get_contents($module.'/src/Templates/Editor/CategoryForm.php');$editorScript=(string)file_get_contents($module.'/src/Templates/Editor/Js/Editor.js');
adminExpenseAssert(strpos($controller,'@FG\\Controller [name=AdminExpense')!==false,'AdminExpense-controller moet als Flexgrid-module zijn geannoteerd.');
adminExpenseAssert(strpos($overview,'TableRenderer')!==false,'Uitgavenoverzicht moet de gedeelde TableRenderer gebruiken.');
adminExpenseAssert(substr_count($overview,'ajax="true"')===1&&strpos($create,'ajax="true"')!==false&&strpos($editor,'ajax="true"')!==false,'Uitgavenformulieren moeten declaratieve Flexgrid-AJAX gebruiken.');
adminExpenseAssert(strpos($create,'button-outline')===false&&strpos($editor,'button-outline')===false,'Uitgavenformulieren mogen button-outline niet gebruiken.');
adminExpenseAssert(strpos($editor,'enctype="multipart/form-data"')!==false&&strpos($editor,'name="attachment"')!==false,'Expense-editor moet bewijsstukken als multipart via het AJAX-formulier aanbieden.');
adminExpenseAssert(strpos($editor,'removeAttachmentAction')!==false&&strpos($editor,'downloadBaseUrl')!==false,'Expense-editor moet beveiligde download en verwijdering aanbieden.');
foreach(['jQuery','$(','fetch(','XMLHttpRequest']as$forbidden){adminExpenseAssert(strpos($script,$forbidden)===false,'Expense-JavaScript bevat verboden transport of jQuery: '.$forbidden);}
adminExpenseAssert(strpos($script,'class AdminExpenseOverview')!==false&&strpos($script,'requestSubmit')!==false,'Uitgavenoverzicht moet een vanilla class bovenop centrale AJAX gebruiken.');
adminExpenseAssert(strpos($create,'<grid class="admin-expense-form-layout"')!==false&&strpos($editor,'<grid class="admin-expense-editor-layout"')!==false&&strpos($style,'.admin-expense-form>*')===false&&strpos($style,'.admin-expense-upload-form > .admin-field { --cw: 8;')!==false,'Uitgaven moeten panels in Flexgrid-grids zetten en formvelden met --cw indelen.');
foreach(['Zakelijke kosten','Loonkosten','Privéopname','Balansbeweging']as$label){adminExpenseAssert(strpos($categoryForm.$controller,$label)!==false,'Categorie-uitleg mist '.$label.'.');}adminExpenseAssert(strpos($categoryForm,'Aftrekbare btw')!==false,'Categorie-uitleg mist Aftrekbare btw.');
adminExpenseAssert(strpos($categoryForm,'updateCategoryAction')!==false&&strpos($overview,'Resultaatkosten zijn niet hetzelfde')!==false,'Frontend moet bestaande categorieën kunnen herclassificeren en het verschil met betalingen uitleggen.');
foreach(['jQuery','$(','fetch(','XMLHttpRequest']as$forbidden){adminExpenseAssert(strpos($editorScript,$forbidden)===false,'Expense-editor-JavaScript bevat verboden transport of jQuery: '.$forbidden);}
echo "AdminExpense UI contract tests passed.\n";
