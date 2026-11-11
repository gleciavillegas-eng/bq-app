<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\ProductImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminProductController extends AbstractController
{
    #[Route('/admin/products', name: 'app_admin_products')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = trim((string) $request->query->get('search', ''));
        $category = trim((string) $request->query->get('category', ''));

        $qb = $em->getRepository(Product::class)->createQueryBuilder('p')->orderBy('p.id', 'DESC');

        if ($search !== '') {
            $qb->andWhere('p.name LIKE :q OR p.description LIKE :q')->setParameter('q', '%' . $search . '%');
        }

        if (in_array($category, Product::CATEGORIES, true)) {
            $qb->andWhere('p.category = :category')->setParameter('category', $category);
        } else {
            $category = '';
        }

        $editId = (int) $request->query->get('edit', 0);

        return $this->render('admin/products.html.twig', [
            'products' => $qb->getQuery()->getResult(),
            'edit_product' => $editId > 0 ? $em->find(Product::class, $editId) : null,
            'categories' => Product::CATEGORIES,
            'statuses' => Product::STATUSES,
            'search' => $search,
            'category_filter' => $category,
        ]);
    }

    #[Route('/admin/products/save', name: 'app_admin_product_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em, ProductImageUploader $uploader): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            if (!$this->isCsrfTokenValid('admin_product_save', (string) $request->request->get('_token'))) {
                throw new \RuntimeException('Invalid security token. Please try again.');
            }

            $id = (int) $request->request->get('id', 0);
            $name = trim((string) $request->request->get('name', ''));
            $description = trim((string) $request->request->get('description', ''));
            $price = (float) $request->request->get('price', 0);
            $category = trim((string) $request->request->get('category', ''));
            $status = trim((string) $request->request->get('status', 'Available'));

            if ($name === '') {
                throw new \RuntimeException('Product name is required.');
            }

            if (strlen($name) > 120) {
                throw new \RuntimeException('Product name is too long.');
            }

            if ($price <= 0) {
                throw new \RuntimeException('Product price must be greater than zero.');
            }

            if (!in_array($category, Product::CATEGORIES, true)) {
                throw new \RuntimeException('Please select a valid product category.');
            }

            if (!in_array($status, Product::STATUSES, true)) {
                $status = 'Available';
            }

            if ($id > 0) {
                $product = $em->find(Product::class, $id);

                if (!$product) {
                    throw new \RuntimeException('Product could not be found.');
                }
            } else {
                $product = new Product();
            }

            $image = $uploader->upload($request->files->get('image'), $product->getImage());

            $product->setName($name)
                ->setDescription($description !== '' ? $description : null)
                ->setPrice(number_format($price, 2, '.', ''))
                ->setCategory($category)
                ->setStatus($status)
                ->setImage($image);

            if ($id > 0) {
                $product->setUpdatedAt(new \DateTimeImmutable());
                $message = 'Product updated successfully.';
            } else {
                $product->setCreatedBy($this->getUser());
                $em->persist($product);
                $message = 'Product added successfully.';
            }

            $em->flush();

            $this->addFlash('success', $message);
        } catch (\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_products');
    }

    #[Route('/admin/products/{id}/delete', name: 'app_admin_product_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em, ProductImageUploader $uploader): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('admin_product_delete', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_admin_products');
        }

        $product = $em->find(Product::class, $id);

        if (!$product) {
            $this->addFlash('error', 'Product could not be found.');

            return $this->redirectToRoute('app_admin_products');
        }

        $name = $product->getName();
        $image = $product->getImage();

        $em->remove($product);
        $em->flush();

        $uploader->delete($image);

        $this->addFlash('success', 'Product "' . $name . '" was deleted successfully.');

        return $this->redirectToRoute('app_admin_products');
    }
}