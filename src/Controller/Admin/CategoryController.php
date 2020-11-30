<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Security\CategoryVoter;
use App\Utils\Slugger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage blog contents in the backend.
 *
 * Please note that the application backend is developed manually for learning
 * purposes. However, in your real Symfony application you should use any of the
 * existing bundles that let you generate ready-to-use backends without effort.
 *
 * See http://knpbundles.com/keyword/admin
 *
 * @Route("/admin/category")
 * @IsGranted("ROLE_ADMIN")
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class CategoryController extends AbstractController
{
    /**
     * Lists all Category entities.
     *
     * This controller responds to two different routes with the same URL:
     *   * 'admin_category_index' is the route with a name that follows the same
     *     structure as the rest of the controllers of this class.
     *   * 'admin_index' is a nice shortcut to the backend homepage. This allows
     *     to create simpler links in the templates. Moreover, in the future we
     *     could move this annotation to any other controller while maintaining
     *     the route name and therefore, without breaking any existing link.
     *
     * @Route("/", methods={"GET"}, name="admin_index")
     * @Route("/", methods={"GET"}, name="admin_category_index")
     */
    public function index(CategoryRepository $categories): Response
    {
        $authorCategories = $categories->findBy(['author' => $this->getUser()], ['publishedAt' => 'DESC']);

        return $this->render('admin/category/index.html.twig', ['categories' => $authorCategories]);
    }

    /**
     * Creates a new Category entity.
     *
     * @Route("/new", methods={"GET", "POST"}, name="admin_category_new")
     *
     * NOTE: the Method annotation is optional, but it's a recommended practice
     * to constraint the HTTP methods each controller responds to (by default
     * it responds to all methods).
     */
    function new (Request $request): Response {
        $category = new Category();
        $category->setAuthor($this->getUser());

        // See https://symfony.com/doc/current/book/forms.html#submitting-forms-with-multiple-buttons
        $form = $this->createForm(CategoryType::class, $category)
            ->add('saveAndCreateNew', SubmitType::class);

        $form->handleRequest($request);

        // the isSubmitted() method is completely optional because the other
        // isValid() method already checks whether the form is submitted.
        // However, we explicitly add it to improve code readability.
        // See https://symfony.com/doc/current/best_practices/forms.html#handling-form-submits
        if ($form->isSubmitted() && $form->isValid()) {
            $category->setSlug(Slugger::slugify($category->getTitle()));

            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            // Flash messages are used to notify the user about the result of the
            // actions. They are deleted automatically from the session as soon
            // as they are accessed.
            // See https://symfony.com/doc/current/book/controller.html#flash-messages
            $this->addFlash('success', 'category.created_successfully');

            if ($form->get('saveAndCreateNew')->isClicked()) {
                return $this->redirectToRoute('admin_category_new');
            }

            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/new.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Category entity.
     *
     * @Route("/{id<\d+>}", methods={"GET"}, name="admin_category_show")
     */
    public function show(Category $category): Response
    {
        // This security check can also be performed
        // using an annotation: @IsGranted("show", subject="category", message="Categories can only be shown to their authors.")
        $this->denyAccessUnlessGranted(CategoryVoter::SHOW, $category, 'Categories can only be shown to their authors.');

        return $this->render('admin/category/show.html.twig', [
            'category' => $category,
        ]);
    }

    /**
     * Displays a form to edit an existing Category entity.
     *
     * @Route("/{id<\d+>}/edit",methods={"GET", "POST"}, name="admin_category_edit")
     * @IsGranted("edit", subject="category", message="Categories can only be edited by their authors.")
     */
    public function edit(Request $request, Category $category): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->setSlug(Slugger::slugify($category->getTitle()));
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'category.updated_successfully');

            return $this->redirectToRoute('admin_category_edit', ['id' => $category->getId()]);
        }

        return $this->render('admin/category/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a Category entity.
     *
     * @Route("/{id}/delete", methods={"POST"}, name="admin_category_delete")
     * @IsGranted("delete", subject="category")
     */
    public function delete(Request $request, Category $category): Response
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_category_index');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($category);
        $em->flush();

        $this->addFlash('success', 'category.deleted_successfully');

        return $this->redirectToRoute('admin_category_index');
    }
}
