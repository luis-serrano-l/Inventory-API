<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Manager\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ProductController extends AbstractController
{
    private $productRepository;
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function index(): JsonResponse
    {
        $products = $this->productRepository->findAll();

        $data = [];
        foreach ($products as $key => $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
                'stock' => $product->getStock()
            ];
        }
        return $this->json($data);
    }

    public function show($id): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException(
                'No product found for id ' . $id
            );
        }
        return $this->json($product);
    }

    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'], $data['price'], $data['stock'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $product = new Product();
        $product->setName($data['name']);
        $product->setPrice($data['price']);
        $product->setStock($data['stock']);

        $entityManager->persist($product);
        $entityManager->flush();

        return $this->json($product);
    }

    public function update($id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) && !isset($data['description']) && !isset($data['price']) && !isset($data['stock'])) {
            return $this->json(['error' => 'Nothing updated'], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException(
                'No product found for id ' . $id
            );
        }

        $updatedFields = [];
        if ($data['name'] != $product->getName()) {
            $product->setName($data['name']);
            $updatedFields['name'] = $data['name'];
        }
        if (isset($data['description']) && $data['description'] != $product->getDescription()) {
            $product->setDescription($data['description']);
            $updatedFields['description'] = $data['description'];
        }
        if ($data['price'] != $product->getPrice()) {
            $product->setPrice($data['price']);
            $updatedFields['price'] = $data['price'];
        }
        if ($data['stock'] != $product->getStock()) {
            $product->setStock($data['stock']);
            $updatedFields['stock'] = $data['stock'];
        }

        $entityManager->persist($product);
        $entityManager->flush();

        if ($updatedFields != []) {
        $msg = "Product with id " . $product->getId() . " was updated successfully.";
        } else {
            $msg = "Product with id " . $product->getId() . " was not updated.";
        }

        return $this->json(['message' => $msg, 'updated_fields' => $updatedFields, 'product' => $product]);
    }
    public function delete($id, EntityManagerInterface $entityManager): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException(
                'No product found for id ' . $id
            );
        }

        $id = $product->getId();
        $entityManager->remove($product);
        $entityManager->flush();

        $msg = "Product '" . $product->getName() . "' with id " . $id . " deleted successfully.";
        return $this->json(['message' => $msg]);
    }
}