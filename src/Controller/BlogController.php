<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Security\Voter\ArticleVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

class BlogController extends AbstractController
{
    /**
     * @Route("/articles", name="blog")
     */
    public function blog(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findBlogArticles();

        return $this->render('blog/blog.html.twig', [
            'articles' => $articles
        ]);
    }

    /**
     * @Route("/article/{id}", name="article")
     */
    public function article(Article $article): Response
    {
        $article->setViews($article->getViews() + 1);

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return $this->render('blog/article.html.twig', [
            'article' => $article
        ]);
    }

    /**
     * @Route("/gestion-blog/mes-articles", name="user_articles")
     */
    public function userArticles() {
        return $this->render('blog/user_articles.html.twig');
    }

    /**
     * @Route("/gestion-blog/article/{id}", name="blog_article_new")
     */
    public function newBlogArticle(
        $id,
        Request $request,
        ArticleRepository $articleRepository
    ) {
        if ($id === 'nouveau') {
            $article = new Article();
            $article->setAuthor($this->getUser());
            $attribute = ArticleVoter::CREATE;
        } else {
            $article = $articleRepository->find($id);
            if (!$article) {
                throw new NotFoundHttpException("Article non trouvé !");
            }
            $attribute = ArticleVoter::UPDATE;
        }

        $this->denyAccessUnlessGranted($attribute, $article);

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                # On récupère l'image depuis le champ du formulaire
                $image = $form->get('image')->getData();
                if ($image) {
                    $path = $this->getParameter('uploads_path');

                    # On créé un nom unique
                    $nameParts = explode('.', $image->getClientOriginalName());
                    $uuid = Uuid::v6();
                    $newName = $nameParts[0].'-'.$uuid.'.'.$nameParts[1];

                    # On déplace l'image dans le dossier uploads
                    $image->move($path, $newName);

                    # On enregistre le nom de l'image, sur notre entité article
                    $article->setImage($newName);
                }

                $em = $this->getDoctrine()->getManager();
                $em->persist($article);
                $em->flush();
                $this->addFlash('success', "L'article a bien été enregistré.");
                return $this->redirectToRoute('blog_article_new', [ 'id' => $article->getId() ]);
            } else {
                $this->addFlash('danger', "Le formulaire comporte des erreurs.");
            }
        }

        return $this->render('blog/new_article.html.twig', [
            'form' => $form->createView(),
            'article' => $article,
        ]);
    }

    /**
     * @Route("/gestion-blog/supprimer-article/{id}", name="blog_article_delete")
     */
    public function deleteBlogArticle(Article $article)
    {
        $this->denyAccessUnlessGranted(ArticleVoter::DELETE, $article);

        $em = $this->getDoctrine()->getManager();
        $em->remove($article);
        $em->flush();

        $this->addFlash('success', "L'article a bien été supprimé.");
        return $this->redirectToRoute('blog');
    }
}
