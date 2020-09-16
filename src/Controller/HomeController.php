<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductTranslation;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;

/**
 * @Route("/{_locale}", requirements={ "_locale": "en|es" }, defaults={"_locale": "en"} )
 */
class HomeController extends Controller
{
    /**
     * @Route("/", name="home")
     */
    public function index(Request $request, ProductRepository $productRepository)
    {
        $products = $productRepository->findAll();

        return $this->render('home/index.html.twig', [
            'products' => $products
        ]);
    }

    /**
     * @Route("/add", name="add")
     */
    public function add(Request $request, EntityManagerInterface $manager){
        $product = new Product();
        $form = $this->createFormBuilder($product)
            ->add('title')
            ->add('description', TextareaType::class)
            ->getForm()
        ;

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $product->addTranslation(new ProductTranslation($request->getLocale(), 'title', $form->get('title')->getData()));
            $product->addTranslation(new ProductTranslation($request->getLocale(), 'description', $form->get('description')->getData()));
            $manager->persist($product);

            $manager->flush();

            return $this->redirectToRoute('home');
        }


        return $this->render('home/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit")
     */
    public function edit(Request $request, Product $product, EntityManagerInterface $manager){
        $form = $this->createFormBuilder($product)
            ->add('title')
            ->add('description', TextareaType::class)
            ->getForm()
        ;

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $titleTranslation = $manager->getRepository(ProductTranslation::class)->findOneBy([
                'object' => $product,
                'locale' => $request->getLocale(),
                'field' => 'title'
            ]);
            if($titleTranslation === null) {
                $product->addTranslation(new ProductTranslation($request->getLocale(), 'title', $form->get('title')->getData()));
            }else{
                $titleTranslation->setContent($form->get('title')->getData());
                $manager->persist($titleTranslation);
            }

            $descriptionTranslation = $manager->getRepository(ProductTranslation::class)->findOneBy([
                'object' => $product,
                'locale' => $request->getLocale(),
                'field' => 'description'
            ]);
            if($descriptionTranslation === null) {
                $product->addTranslation(new ProductTranslation($request->getLocale(), 'description', $form->get('description')->getData()));
            }else{
                $descriptionTranslation->setContent($form->get('description')->getData());
                $manager->persist($descriptionTranslation);
            }
            $manager->persist($product);

            $manager->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render('home/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/delete/{id}", name="delete", methods={"POST"})
     */
    public function delete(Request $request, EntityManagerInterface $manager, Product $product){
        if(!$this->isCsrfTokenValid('delete_product'.$product->getId(), $request->request->get('csrf_token'))){
            return new InvalidCsrfTokenException('CSRF Token invalid');
        }

        $manager->remove($product);
        $manager->flush();

        return $this->redirectToRoute('home');
    }
}
