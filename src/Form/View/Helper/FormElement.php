<?php
/**
 * Zf3Bootstrap4
 */

namespace Zf3Bootstrap4\Form\View\Helper;

use Zend\Form\ElementInterface;
use Zend\Form\View\Helper\FormElement as ZendFormElement;
use Zend\Form\View\Helper\FormElementErrors;
use Zend\Form\View\Helper\FormLabel;
use Zend\View\Helper\EscapeHtml;

/**
 * Form Element
 */
class FormElement extends ZendFormElement
{
    /**
     * @var \Zend\Form\View\Helper\FormLabel
     */
    protected $labelHelper;

    /**
     * @var ZendFormElement
     */
    protected $elementHelper;

    /**
     * @var \Zend\View\Helper\EscapeHtml
     */
    protected $escapeHelper;

    /**
     * @var \Zend\Form\View\Helper\FormElementErrors
     */
    protected $elementErrorHelper;

    /**
     * @var FormDescription
     */
    protected $descriptionHelper;
    /**
     * @var string
     */
    protected $groupWrapper = '<div class="form-group %s" id="control-group-%s">%s</div>';

    /**
     * @var string
     */
    protected $controlWrapper = '<div class="col-sm-10">%s%s%s</div>';
    /**
     * @var bool
     */
    protected $inline = false;


    /**
     * Render
     *
     * @param  \Zend\Form\ElementInterface $element
     * @param  string $groupWrapper
     * @param  string $controlWrapper
     *
     * @return string
     */
    public function render(ElementInterface $element, $groupWrapper = null, $controlWrapper = null)
    {
        $labelHelper = $this->getLabelHelper();
        $escapeHelper = $this->getEscapeHtmlHelper();
        $elementHelper = $this->getElementHelper();
        $elementErrorHelper = $this->getElementErrorHelper();
        $descriptionHelper = $this->getDescriptionHelper();
        $groupWrapper = $groupWrapper ?: $this->groupWrapper;
        $controlWrapper = $controlWrapper ?: $this->controlWrapper;

        $hiddenElementForCheckbox = '';
        if (method_exists($element, 'useHiddenElement') && $element->useHiddenElement()) {
            // If we have hidden input with checkbox's unchecked value, render that separately so it can be prepended later, and unset it in the element
            $withHidden = $elementHelper->render($element);
            $withoutHidden = $elementHelper->render($element->setUseHiddenElement(false));
            $hiddenElementForCheckbox = str_ireplace($withoutHidden, '', $withHidden);
        }

        $id = $element->getAttribute('id') ?: $element->getAttribute('name');

        /**
         * Dedicated control-wrapper for inline forms
         */
        if ($element->getOption('inline')) {
            $controlWrapper = '%s%s%s';
        }

        $classes = [$element->getAttribute('class')];

        //Different form elements require different classes
        switch (true) {
            case $element instanceof \Zend\Form\Element\Radio:
            case $element instanceof \Zend\Form\Element\Checkbox:
                $classes[] = 'form-check-input';
                break;
            case $element instanceof \Zend\Form\Element\Hidden:
            case $element instanceof \Zend\Form\Element\Button:
            case $element instanceof \Zend\Form\Element\Submit:
                break;
            default:
                $classes[] = 'form-control';
                break;
        }

        //Add the is-invalid when no suited option is given
        if (!empty($element->getMessages())) {
            $classes[] = 'is-invalid';
        }

        $element->setAttribute('class', implode(' ', $classes));


        $controlLabel = '';
        $label = $element->getLabel();
        if (strlen($label) === 0) {
            $label = $element->getOption('label') ?: $element->getAttribute('label');
        }

        if ($label && !$element->getOption('skipLabel')) {

            $controlLabel .= $labelHelper->openTag(
                [
                    'class' => $element->getOption('wrapCheckboxInLabel') ? '' : 'form-control-label col-sm-2',
                ] + ($element->hasAttribute('id') ? ['for' => $id] : [])
            );

            if (null !== ($translator = $labelHelper->getTranslator())) {
                $label = $translator->translate(
                    $label,
                    $labelHelper->getTranslatorTextDomain()
                );
            }
            if ($element->getOption('wrapCheckboxInLabel')) {
                $controlLabel .= $elementHelper->render($element) . ' ';
            }
            if ($element->getOption('skipLabelEscape')) {
                $controlLabel .= $label;
            } else {
                $controlLabel .= $escapeHelper($label);
            }
            $controlLabel .= $labelHelper->closeTag();
        }

        if ($element->getOption('wrapCheckboxInLabel')) {
            $controls = $controlLabel;
            $controlLabel = '';
        } else {
            $controls = $elementHelper->render($element);
        }



        //Wrap the checkboxes in special form-check-elements
        if ($element instanceof \Zend\Form\Element\Checkbox) {
            $controls = str_replace(
                ['<label', '</label>'],
                ['<div class="form-check"><label class="form-check-label"', '</label></div>'],
                $controls
            );
        }

        if ($element instanceof \Zend\Form\Element\Hidden ||
            $element instanceof \Zend\Form\Element\Submit ||
            $element instanceof \Zend\Form\Element\Button
        ) {
            return $controls . $elementErrorHelper->render($element);
        }



        //As of Bootstrap 4 the error element is wrapped in a div
        $errorElement = $elementErrorHelper->render($element);
        if (!empty($errorElement)) {
            $errorElement = sprintf('<div class="invalid-feedback">%s</div>', $errorElement);
        }

        $html = $hiddenElementForCheckbox . $controlLabel . sprintf(
                $controlWrapper,
                 $controls,
                $descriptionHelper->render($element),
                $errorElement
            );

        return sprintf($groupWrapper, (!$this->inline ? 'row' : ''), $id, $html);
    }

    /**
     * Magical Invoke
     *
     * @param  \Zend\Form\ElementInterface $element
     * @param  string $groupWrapper
     * @param  string $controlWrapper
     *
     * @return string|self
     */
    public function __invoke(ElementInterface $element = null, $groupWrapper = null, $controlWrapper = null)
    {
        if ($element) {
            return $this->render($element, $groupWrapper, $controlWrapper);
        }

        return $this;
    }

    /**
     * Set Label Helper
     *
     * @param  \Zend\Form\View\Helper\FormLabel $labelHelper
     *
     * @return self
     */
    public function setLabelHelper(FormLabel $labelHelper)
    {
        $labelHelper->setView($this->getView());
        $this->labelHelper = $labelHelper;

        return $this;
    }

    /**
     * Get Label Helper
     *
     * @return \Zend\Form\View\Helper\FormLabel
     */
    public function getLabelHelper()
    {
        if (!$this->labelHelper) {
            $this->setLabelHelper($this->view->plugin('formlabel'));
        }

        return $this->labelHelper;
    }

    /**
     * Set EscapeHtml Helper
     *
     * @param  \Zend\View\Helper\EscapeHtml $escapeHelper
     *
     * @return self
     */
    public function setEscapeHtmlHelper(EscapeHtml $escapeHelper)
    {
        $escapeHelper->setView($this->getView());
        $this->escapeHelper = $escapeHelper;

        return $this;
    }

    /**
     * Get EscapeHtml Helper
     *
     * @return \Zend\View\Helper\EscapeHtml
     */
    public function getEscapeHtmlHelper()
    {
        if (!$this->escapeHelper) {
            $this->setEscapeHtmlHelper($this->view->plugin('escapehtml'));
        }

        return $this->escapeHelper;
    }

    /**
     * Set Element Helper
     *
     * @param  \Zend\Form\View\Helper\FormElement $elementHelper
     *
     * @return self
     */
    public function setElementHelper(ZendFormElement $elementHelper): self
    {
        $elementHelper->setView($this->getView());
        $this->elementHelper = $elementHelper;

        return $this;
    }

    /**
     * Get Element Helper
     *
     * @return \Zend\Form\View\Helper\FormElement
     */
    public function getElementHelper(): \Zend\Form\View\Helper\FormElement
    {
        if (!$this->elementHelper) {
            $this->setElementHelper($this->view->plugin('formelement'));
        }

        return $this->elementHelper;
    }

    /**
     * Set Element Error Helper
     *
     * @param  \Zend\Form\View\Helper\FormElementErrors $errorHelper
     *
     * @return self
     */
    public function setElementErrorHelper(FormElementErrors $errorHelper): self
    {
        $errorHelper->setView($this->getView());

        $this->elementErrorHelper = $errorHelper;

        return $this;
    }

    /**
     * Get Element Error Helper
     *
     * @return \Zend\Form\View\Helper\FormElementErrors
     */
    public function getElementErrorHelper(): FormElementErrors
    {
        if (!$this->elementErrorHelper) {
            $this->setElementErrorHelper($this->view->plugin('formelementerrors'));
        }

        return $this->elementErrorHelper;
    }

    /**
     * Set Description Helper
     *
     * @param FormDescription
     *
     * @return self
     */
    public function setDescriptionHelper(FormDescription $descriptionHelper): self
    {
        $descriptionHelper->setView($this->getView());
        $this->descriptionHelper = $descriptionHelper;

        return $this;
    }

    /**
     * Get Description Helper
     *
     * @return FormDescription
     */
    public function getDescriptionHelper(): FormDescription
    {
        if (!$this->descriptionHelper) {
            $this->setDescriptionHelper($this->view->plugin('ztbformdescription'));
        }

        return $this->descriptionHelper;
    }

    /**
     * Set Group Wrapper
     *
     * @param  string $groupWrapper
     *
     * @return self
     */
    public function setGroupWrapper($groupWrapper): self
    {
        $this->groupWrapper = (string)$groupWrapper;

        return $this;
    }

    /**
     * Get Group Wrapper
     *
     * @return string
     */
    public function getGroupWrapper(): string
    {
        return $this->groupWrapper;
    }

    /**
     * Set Control Wrapper
     *
     * @param  string $controlWrapper ;
     *
     * @return self
     */
    public function setControlWrapper($controlWrapper): self
    {
        $this->controlWrapper = (string)$controlWrapper;

        return $this;
    }

    /**
     * Get Control Wrapper
     *
     * @return string
     */
    public function getControlWrapper(): string
    {
        return $this->controlWrapper;
    }
}
