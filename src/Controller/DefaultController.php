<?php

namespace App\Controller;

use App\Entity\OrderProduct;
use App\Entity\Orders;
use Doctrine\Common\Persistence\ObjectManager;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Faker\Factory;

class DefaultController extends AbstractController
{

    /**
     * @var ObjectManager
     */
    private $em;

    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
    }

    public function index()
    {

        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);

        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('pdf/defaultPDF.html.twig', [
            'title' => "Commande n° 50",
        ]);

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation 'portrait' or 'paysage'
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Store PDF Binary Data
        $output = $dompdf->output();

        // In this case, we want to write the file in the public directory
        $publicDirectory = $this->getParameter('kernel.project_dir'). '/public/factures';

        // e.g /var/www/project/public/mypdf.pdf
        $pdfFilepath =  $publicDirectory . '/commande'. 50 .'.pdf';

        // Write file to the desired path
        file_put_contents($pdfFilepath, $output);

        // Send some text response
        return $pdfFilepath;
    }



    public function bill(Orders $orders)
    {

        $orderProd = $orders->getOrderProducts();

        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('httpContent', '');
        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);

        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('pdf/facture.html.twig', [
            'order' => $orders,
            'products' => $orderProd
        ]);

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation 'portrait' or 'paysage'
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Store PDF Binary Data
        $output = $dompdf->output();

        // In this case, we want to write the file in the public directory
        $publicDirectory = $this->getParameter('kernel.project_dir') . '/public/factures';

        // e.g /var/www/project/public/mypdf.pdf
        $pdfFilepath = $publicDirectory . '/commande' . $orders->getId() . '.pdf';

        // Write file to the desired path
        file_put_contents($pdfFilepath, $output);

        $dompdf->stream($pdfFilepath, [
            "Attachment" => false
        ]);

        $orders->setInvoice($pdfFilepath);
        $orders->setPay(true);
        $orders->setPaydate(new \DateTime());

        $this->em->persist($orders);
        $this->em->flush();
    }

}