<?php

/**
 * @author Sergey Pryadkin <GiperProger@gmail.com>
 * @package ow.ow_plugins.photver.classes
 * @since 1.8.4
 */
class PHOTVER_CMP_AdminDeclineFloatbox extends OW_Component
{
    private $formName;
    public $floatboxData;

    public function __construct($formName)
    {
        parent::__construct();

        $this->formName = $formName;
        $this->floatboxData = array();
    }

    public function addFloatbox($triggerId, $confirmingActionId, $title, $text)
    {
        $unique = uniqid();

        $this->floatboxData[] = array(
            'triggerId' => $triggerId,
            'confirmingActionId' => $confirmingActionId,
            'floatboxId' => 'floatbox_' . $unique,
            'confirmId' => 'decline_submit_id',
            'floatboxTitle' => $title,
            'floatboxText' => $text
        );
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        if( empty($this->floatboxData) )
        {
            return;
        }

        $document = OW::getDocument();

        foreach( $this->floatboxData as $fb )
        {
            $script = '(function(){
                var floatBox;

                $(".ow_small #' . $fb['triggerId'] . '").click(function() {
                    var $form_content = $("#' . $fb['floatboxId'] . '").children();
                    $(this).parents("tr").find("input[type=\"checkbox\"]").prop("checked", true);

                    floatBox = new OW_FloatBox({
                        $title: "' . $fb['floatboxTitle'] . '",
                        $contents: $form_content,
                        icon_class: "ow_ic_delete",
                        width: 450
                    });

                    return false;
                });

                $("#' . $fb['confirmId'] . '").click(function() {
                    var $form = $("#' . $this->formName . '");

                    $form.append("<input type=\"hidden\" name=\"' . $fb['confirmingActionId'] . '\" value=\"' . $fb['confirmingActionId'] . '\" />");
                    var reason_text = $("textarea#reason_decline").val();
                    $form.append("<input type=\"hidden\" id=\"reason_id\" name=\"decline_reason\" />");

                                        $("#reason_id").val(reason_text);


                    $form.submit();
                });

                $("#' . $fb['confirmId'] . '-no").click(function(e) {
                    floatBox.close();
                    e.preventDefault();
                });
            })();';

            $document->addOnloadScript($script);
        }

        $this->assign('floatboxData', $this->floatboxData);
    }
}