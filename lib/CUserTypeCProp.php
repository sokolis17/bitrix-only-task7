<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(FILE);

class CUserTypeCProp extends CUserTypeString
{
    public static function GetUserTypeDescription()
    {
        return array(
            "USER_TYPE_ID" => "my_complex_uf",
            "CLASS_NAME" => "CUserTypeCProp",
            "DESCRIPTION" => "Мое комплексное свойство (UF)",
            "BASE_TYPE" => "string",
        );
    }

    public static function GetDBColumnType($arUserField)
    {
        return "text";
    }

    public static function GetEditFormHTML($arUserField, $arHtmlControl)
    {
        $iIBlockId = 0; 
        $value = $arUserField['VALUE'];

        if (!is_array($value) && CheckSerializedData($value)) {
            $value = unserialize($value);
        }

        $arFieldsConfig = [];
        if (!empty($arUserField['SETTINGS']['FIELDS_SCHEMA'])) {
             $arFieldsConfig = json_decode($arUserField['SETTINGS']['FIELDS_SCHEMA'], true);
        }
        
        if (empty($arFieldsConfig)) {
            return '<div>Настройки полей не заданы. Зайдите в настройки поля и укажите JSON схему.</div>';
        }

        $html = '<table style="border-collapse: collapse; width: 100%;">';

        foreach ($arFieldsConfig as $code => $field) {
            $fieldName = $arHtmlControl['NAME'] . '[' . $code . ']';
            $fieldVal = isset($value[$code]) ? $value[$code] : '';
            
            $html .= '<tr><td style="padding: 5px; width: 30%; text-align: right;">' . htmlspecialcharsbx($field['TITLE']) . ':</td>';
            $html .= '<td style="padding: 5px;">';

            
            if ($field['TYPE'] == 'html') {
                ob_start();
                if (!class_exists('\CLightHTMLEditor')) \CModule::IncludeModule("fileman");
                
                $editorId = 'uf_editor_' . md5($fieldName . mt_rand());
                
                $LHE = new \CLightHTMLEditor;
                $LHE->Show(array(
                    'id' => $editorId,
                    'width' => '100%',
                    'height' => '200px',
                    'inputName' => $fieldName,
                    'content' => $fieldVal,
                    'bUseFileManager' => true,
                    'bFloatingToolbar' => false,
                    'bArisingToolbar' => true,
                ));
                $html .= ob_get_clean();
            } 
            elseif ($field['TYPE'] == 'text') {
                $html .= '<textarea name="'.$fieldName.'" rows="3" style="width:100%">'.htmlspecialcharsbx($fieldVal).'</textarea>';
            }
            else {
                $html .= '<input type="text" name="'.$fieldName.'" value="'.htmlspecialcharsbx($fieldVal).'" style="width:100%">';
            }

            $html .= '</td></tr>';
        }

        $html .= '</table>';

        return $html;
    }

    public static function OnBeforeSave($arUserField, $value)
    {
        // $value приходит как массив, где ключи - коды наших под-полей
        if (is_array($value)) {
            return serialize($value);
        }
        return $value;
    }

    public static function GetSettingsHTML($arUserField, $arHtmlControl, $bVarsFromForm)
    {
        $val = '';
        if ($bVarsFromForm) {
            $val = $GLOBALS[$arHtmlControl["NAME"]]["FIELDS_SCHEMA"];
        } elseif (is_array($arUserField)) {
            $val = $arUserField["SETTINGS"]["FIELDS_SCHEMA"];
        }
        $html = '<tr>
            <td>Схема полей (JSON):<br>
            <small>Пример:<br>
            {"field1":{"TITLE":"Заголовок","TYPE":"string"},<br>
             "field2":{"TITLE":"Описание","TYPE":"html"}}
            </small>
            </td>
            <td>
                <textarea name="'.$arHtmlControl["NAME"].'[FIELDS_SCHEMA]" rows="10" cols="60">'.htmlspecialcharsbx($val).'</textarea>
            </td>
        </tr>';

        return $html;
    }
}