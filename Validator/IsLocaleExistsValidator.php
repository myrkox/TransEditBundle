<?php

namespace Shas\TransEditBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Shas\TransEditBundle\Services\TranslationContentProcessing;

class IsLocaleExistsValidator extends ConstraintValidator
{
    public $groups;

    /** @var TranslationContentProcessing */
    private $translationContentProcessing;

    /**
     * @param TranslationContentProcessing $translationContentProcessing
     */
    public function setTranslationContentProcessing($translationContentProcessing)
    {
        $this->translationContentProcessing = $translationContentProcessing;
    }

    /**
     * @param $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if($this->translationContentProcessing->isLocaleExists($value)){
            $this->context->addViolation('Locale "'.$value.'" is already exists.');
        }
    }
}
