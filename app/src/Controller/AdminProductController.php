<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminProductController extends AbstractController
{
    private const CATEGORIES = ['Burgers', 'Chicken', 'Fries', 'Sides', 'Drinks', 'Desserts', 'Combos'];
    private const STATUSES = ['Available', 'Unavailable'];
    private const IMAGE_DIR = 'assets/uploads/products/';

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $projectDir
    ) {
    }

    /* =========================================================
       LIST + FORM
    ========================================================= */

    #[Route('/admin/products', name: 'app_admin_products')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $editId = (int) $request->query->get('edit', 0);
        $editProduct = $editId > 0 ? $em->find(Product::class, $editId) : null;

        return $this->render('admin/products.html.twig', [
            'products' => $em->getRepository(Product::class)->findBy([], ['id' => 'DESC']),
            'edit_product' => $editProduct,
            'categories' => self::CATEGORIES,
            'statuses' => self::STATUSES,
        ]);
    }

    /* =========================================================
       ADD / EDIT
    ========================================================= */

    #[Route('/admin/products/save', name: 'app_admin_product_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): RedirectResponse
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

            if (!in_array($category, self::CATEGORIES, true)) {
                throw new \RuntimeException('Please select a valid product category.');
            }

            if (!in_array($status, self::STATUSES, true)) {
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

            $image = $this->uploadImage($request->files->get('image'), $product->getImage());

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

    /* =========================================================
       DELETE
    ========================================================= */

    #[Route('/admin/products/{id}/delete', name: 'app_admin_product_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): RedirectResponse
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

        // Ang order_items.product_id mahimong NULL, busa dili mawala ang order history
        $em->remove($product);
        $em->flush();

        $this->deleteImage($image);

        $this->addFlash('success', 'Product "' . $name . '" was deleted successfully.');

        return $this->redirectToRoute('app_admin_products');
    }

    /* =========================================================
       IMAGE HELPERS
    ========================================================= */

    private function uploadImage(?UploadedFile $file, ?string $oldImage): ?string
    {
        // Walay gipili nga bag-ong picture: magpabilin ang karaan
        if (!$file) {
            return $oldImage;
        }

        if (!$file->isValid()) {
            throw new \RuntimeException('Unable to upload the product image. Make sure it is 5 MB or smaller.');
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \RuntimeException('Product image must be 5 MB or smaller.');
        }

        $info = @getimagesize($file->getPathname());

        if ($info === false) {
            throw new \RuntimeException('Please upload a valid JPG or PNG image.');
        }

        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        $mime = $info['mime'] ?? '';

        if (!isset($types[$mime])) {
            throw new \RuntimeException('Only JPG and PNG images are allowed.');
        }

        $directory = $this->projectDir . '/public/' . self::IMAGE_DIR;

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create the product image folder.');
        }

        $safeName = trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)), '-');

        if ($safeName === '') {
            $safeName = 'product';
        }

        $filename = strtolower($safeName) . '-' . bin2hex(random_bytes(6)) . '.' . $types[$mime];

        try {
            $file->move($directory, $filename);
        } catch (\Throwable) {
            throw new \RuntimeException('Unable to save the product image.');
        }

        // I-delete ang karaan nga picture human lang ma-upload ang bag-o
        $this->deleteImage($oldImage);

        return self::IMAGE_DIR . $filename;
    }

    private function deleteImage(?string $path): void
    {
        $path = trim((string) $path);

        // Ang products/uploads folder lang ang pwede i-delete
        if ($path === '' || !str_starts_with($path, self::IMAGE_DIR)) {
            return;
        }

        $full = $this->projectDir . '/public/' . self::IMAGE_DIR . basename($path);

        if (is_file($full)) {
            @unlink($full);
        }
    }
}