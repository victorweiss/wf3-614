<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function findBlogArticles(): array {
        return $this->findBy(
            [],
            [ 'views' => 'DESC' ]
        );
    }

    public function findPopularArticles(int $limit = 3) {
        return $this->findBy(
            [ 'status' => Article::STATUS_PUBLISHED ],
            [ 'views' => 'desc' ],
            $limit
        );
    }
}
