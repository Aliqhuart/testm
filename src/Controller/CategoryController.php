<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Category;
use App\Events;
use App\Form\CommentType;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use App\Repository\TagRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage blog contents in the public part of the site.
 *
 * @Route("/category")
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class CategoryController extends AbstractController
{
    /**
     * @Route("/", defaults={"page": "1", "_format"="html"}, methods={"GET"}, name="category_index")
     * @Route("/rss.xml", defaults={"page": "1", "_format"="xml"}, methods={"GET"}, name="blog_rss")
     * @Route("/page/{page<[1-9]\d*>}", defaults={"_format"="html"}, methods={"GET"}, name="blog_index_paginated")
     * @Cache(smaxage="10")
     *
     * NOTE: For standard formats, Symfony will also automatically choose the best
     * Content-Type header for the response.
     * See https://symfony.com/doc/current/quick_tour/the_controller.html#using-formats
     */
    public function index(Request $request, int $page, string $_format, CategoryRepository $categories): Response
    {
        $latestCategories = $categories->findLatest($page);

        // Every template name also has two extensions that specify the format and
        // engine for that template.
        // See https://symfony.com/doc/current/templating.html#template-suffix
        return $this->render('category/index.' . $_format . '.twig', ['categories' => $latestCategories]);
    }

    /**
     * @Route("/search", methods={"GET"}, name="blog_search")
     */
    public function search(Request $request, CategoryRepository $categories): Response
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->render('category/search.html.twig');
        }

        $query = $request->query->get('s', '');
        $limit = $request->query->get('l', 10);
        $foundCategories = $categories->findBySearchQuery($query, $limit);

        $results = [];
        foreach ($foundCategories as $category) {
            $results[] = [
                'title' => htmlspecialchars($category->getTitle(), ENT_COMPAT | ENT_HTML5),
                'date' => $category->getPublishedAt()->format('M d, Y'),
                'author' => htmlspecialchars($category->getAuthor()->getFullName(), ENT_COMPAT | ENT_HTML5),
                'url' => $this->generateUrl('category_post', ['slug' => $category->getSlug()]),
            ];
        }

        return $this->json($results);
    }

    /**
     * @Route("/categories/{slug}", methods={"GET"}, name="category_post")
     *
     * NOTE: The $category controller argument is automatically injected by Symfony
     * after performing a database query looking for a Category with the 'slug'
     * value given in the route.
     * See https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html
     */
    public function categoryShow(Category $category): Response
    {
        // Symfony's 'dump()' function is an improved version of PHP's 'var_dump()' but
        // it's not available in the 'prod' environment to prevent leaking sensitive information.
        // It can be used both in PHP files and Twig templates, but it requires to
        // have enabled the DebugBundle. Uncomment the following line to see it in action:
        //
        // dump($post, $this->getUser(), new \DateTime());

        return $this->render('category/category_show.html.twig', ['category' => $category]);
    }
}
