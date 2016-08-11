<?php

namespace Shas\TransEditBundle\Controller;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Shas\TransEditBundle\Services\TranslationContentProcessing;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Shas\TransEditBundle\Validator\Constraints\IsLocaleExistsConstraint;

class DefaultController extends Controller
{
    /** @var TranslationContentProcessing $translationContentProcessing */
    private $translationContentProcessing;

    /**
     * @return bool
     */
    private function makeTranslationContentProcessing()
    {
        $this->translationContentProcessing = $this->get('shas.translation_content_processing');

        if ($this->translationContentProcessing->getTranslationContentStatus() > 0) {
            return false;
        }

        return true;
    }

    public function indexAction()
    {
        if (!$this->makeTranslationContentProcessing()) {
            return $this->redirectToRoute('trans_edit_global_error');
        }

        return $this->render(
            'TransEditBundle:Default:statistic.html.twig',
            ['menuItem' => 'statistic', 'statistic' => $this->translationContentProcessing->getStatistic()]
        );
    }

    public function createNewFileAction(Request $request)
    {
        if (!$this->makeTranslationContentProcessing()) {
            return $this->redirectToRoute('trans_edit_global_error');
        }

        $form = $this->createFormBuilder()
            ->add('locales', ChoiceType::class, [
                'label'    => 'Choose locales:',
                'multiple' => true,
                'required' => true,
                'choices'  => $this->translationContentProcessing->getDefaultLocales()
            ])
            ->add('save', SubmitType::class, ['label' => 'Create file'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $locales = $form->get('locales')->getData();

            if ($this->translationContentProcessing->createNewTranslationContentEntity(array_values($locales))) {
                return $this->render(
                    'TransEditBundle:Default:new_file_success.html.twig',
                    ['menuItem' => 'create_new_file']
                );
            } else {
                return $this->render(
                    'TransEditBundle:Default:new_file_error.html.twig',
                    ['menuItem' => 'create_new_file']
                );
            }
        }

        return $this->render(
            'TransEditBundle:Default:new_file.html.twig',
            ['menuItem' => 'create_new_file', 'form' => $form->createView()]
        );
    }

    public function addLocaleAction(Request $request)
    {
        if (!$this->makeTranslationContentProcessing()) {
            return $this->redirectToRoute('trans_edit_global_error');
        }

        $form = $this->createFormBuilder()
            ->add('localeKey', TextType::class, [
                'label'       => 'Enter locale\'s code:',
                'required'    => true,
                'constraints' => new IsLocaleExistsConstraint()
            ])
            ->add('localeName', TextType::class, [
                'label'    => 'Enter locale\'s name:',
                'required' => true
            ])
            ->add('save', SubmitType::class, ['label' => 'Add locale'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $localeKey = $form->get('localeKey')->getData();
            $localeName = $form->get('localeName')->getData();

            if (!$this->makeTranslationContentProcessing()) {
                return $this->redirectToRoute('trans_edit_global_error');
            }

            if ($this->translationContentProcessing->addNewLocale($localeKey, $localeName)) {
                return $this->render(
                    'TransEditBundle:Default:add_locale_success.html.twig',
                    ['menuItem' => 'add_locale']
                );
            } else {
                return $this->render(
                    'TransEditBundle:Default:add_locale_error.html.twig',
                    ['menuItem' => 'add_locale']
                );
            }
        }

        return $this->render(
            'TransEditBundle:Default:add_locale.html.twig',
            ['menuItem' => 'add_locale', 'form' => $form->createView()]
        );
    }

    public function removeLocaleAction(Request $request)
    {
        if (!$this->makeTranslationContentProcessing()) {
            return $this->redirectToRoute('trans_edit_global_error');
        }

        $locales = $this->translationContentProcessing->getExistsLocales();

        $form = $this->createFormBuilder()
            ->add('locale', ChoiceType::class, [
                'label'    => 'Choose locale:',
                'multiple' => false,
                'required' => true,
                'disabled' => count($locales) == 1 ? true : false,
                'choices'  => $locales
            ])
            ->add('save', SubmitType::class, [
                    'label'    => 'Delete locale',
                    'disabled' => count($locales) == 1 ? true : false,
                ]
            )
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $localeKey = $form->get('locale')->getData();

            if (!$this->makeTranslationContentProcessing()) {
                return $this->redirectToRoute('trans_edit_global_error');
            }

            if ($this->translationContentProcessing->removeLocale($localeKey)) {
                return $this->render(
                    'TransEditBundle:Default:remove_locale_success.html.twig',
                    ['menuItem' => 'remove_locale']
                );
            } else {
                return $this->render(
                    'TransEditBundle:Default:remove_locale_error.html.twig',
                    ['menuItem' => 'remove_locale']
                );
            }
        }

        return $this->render(
            'TransEditBundle:Default:remove_locale.html.twig',
            ['menuItem' => 'remove_locale', 'form' => $form->createView(), 'locales' => $locales]
        );
    }

    public function allKeysAction()
    {
        if (!$this->makeTranslationContentProcessing()) {
            return $this->redirectToRoute('trans_edit_global_error');
        }

        $allKeysData = $this->translationContentProcessing->getAllKeysPlain();

        return $this->render(
            'TransEditBundle:Default:all_keys.html.twig',
            [
                'languages'    => $allKeysData['languages'],
                'translations' => $allKeysData['translations'],
                'menuItem'     => 'all_keys'
            ]
        );
    }

    public function groupsKeysAction()
    {
        if (!$this->makeTranslationContentProcessing()) {
            return $this->redirectToRoute('trans_edit_global_error');
        }

        $localeKeys = $this->translationContentProcessing->getLocaleKeys();
        $groupsKeysData = $this->translationContentProcessing->getGroupsKeys();

        return $this->render(
            'TransEditBundle:Default:groups_keys.html.twig',
            ['groupsKeysData' => $groupsKeysData, 'locales' => $localeKeys, 'menuItem' => 'groups_keys']
        );
    }

    public function newKeyAction($key = null)
    {
        if (!$this->makeTranslationContentProcessing()) {
            return $this->redirectToRoute('trans_edit_global_error');
        }

        return $this->render(
            'TransEditBundle:Default:new_key.html.twig',
            [
                'localeKeys' => $this->translationContentProcessing->getLocaleKeys(),
                'key'        => $key,
                'keyData'    => $this->translationContentProcessing->findKeyData($key),
                'menuItem'   => 'new_key'
            ]
        );

    }

    public function searchKeysAction(Request $request)
    {
        $needle = $request->get('needle');

        if (!$this->makeTranslationContentProcessing()) {
            return $this->redirectToRoute('trans_edit_global_error');
        }

        $foundKeys = $this->translationContentProcessing->searchKeys($needle);

        return $this->render(
            'TransEditBundle:Default:found_keys.html.twig',
            [
                'foundKeys' => $foundKeys,
                'needle'    => $needle,
                'menuItem'  => 'found_keys'
            ]
        );

    }

    public function getAllKeysByAjaxAction()
    {
        if (!$this->makeTranslationContentProcessing()) {
            return $this->redirectToRoute('trans_edit_global_error');
        }

        return new JsonResponse($this->translationContentProcessing->getAllTransKeys());
    }

    public function saveKeyDataByAjaxAction(Request $request)
    {
        $dataJson = null;
        if ($request->getMethod() == 'POST') {
            $dataJson = $request->get('data');
        }

        if (empty($dataJson)) {
            return new Response(null, 400);
        }

        $data = json_decode($dataJson, true);

        if (!$this->makeTranslationContentProcessing()) {
            return $this->redirectToRoute('trans_edit_global_error');
        }

        $result = $this->translationContentProcessing->saveKeyData($data);

        return new JsonResponse(['status' => $result]);
    }

    public function findKeyDataByAjaxAction(Request $request)
    {
        $key = null;
        if ($request->getMethod() == 'POST') {
            $key = $request->get('data');
        }

        if (empty($key)) {
            return new JsonResponse(['status' => false]);
        }

        if (!$this->makeTranslationContentProcessing()) {
            return $this->redirectToRoute('trans_edit_global_error');
        }

        return new JsonResponse(
            [
                'status'  => true,
                'keyData' => $this->translationContentProcessing->findKeyData($key)
            ]
        );
    }

    public function globalErrorAction()
    {
        return $this->render('TransEditBundle:Default:global_error.html.twig', ['menuItem' => 'global_error']);
    }
}
