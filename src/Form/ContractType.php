<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use App\Entity\User;
use App\Entity\Contract;
use App\Entity\Product;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ContractType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('label',TextType::class,array('label' => 'Nom * (exemple : pommes mai 2020)',     'required' => true))
          //  ->add('isActive',CheckboxType::class,array('label' => 'Ouvrir les commandes dès maintenant',     'required' => false))
            ->add('periodStart',DateType::class,array(
              'widget' => 'single_text',
              'html5' => false,
              'format' =>'yyyy-MM-dd',
              'label' => 'Date début distributions (année-mois-jour) *', 
              'required' => true))
            ->add('periodEnd',DateType::class,array(
              'widget' => 'single_text',
              'html5' => false,
              'format' =>'yyyy-MM-dd',
              'label' => 'Date fin distributions (année-mois-jour) *', 
              'required' => true))
            ->add('fillDateEnd',DateType::class,array(
              'widget' => 'single_text',
              'html5' => false,
              'format' =>'yyyy-MM-dd',
              'label' => 'A remplir au plus tard le (année-mois-jour) (1)', 
              'required' => false))
            //->add('isVisible',CheckboxType::class,array('label' => 'Visible pour les adhérents','required' => false))
            ->add('description',TextareaType::class,array('label' => 'Infos','required' => false))
            ->add('countPurchaseSince',DateType::class,array(
              'widget' => 'single_text',
              'html5' => false,
              'format' =>'yyyy-MM-dd',
              'label' => '(optionnel) Compter les produits déjà commandés depuis le (année-mois-jour)', 
              'required' => false))
            ->add('products',EntityType::class,array(
              'label' => 'Produits',
              'class' => Product::class, 
              'expanded' => true,
              'multiple' => true,                   
              'required' => true,
              'choice_label' => 'getLabelForCheckbox',
              'query_builder' => function (EntityRepository $er) use ($options)
              {
                  if ($options['user']->getIsAdmin())
                  {
                    $qb = $er->createQueryBuilder('p')
                      ->leftJoin('App\Entity\Farm','f','WITH','f.idFarm = p.fkFarm')
                      ->orderBy('f.sequence, p.sequence');
                    if ($options['is_new'])//nouveau contrat : on n'affiche que les produits actifs des fermes actives
                    {
                      $qb->where('p.isActive=1')
                        ->andWhere('f.isActive=1');                      
                    }
                    return $qb;
                  }
                  else
                  {
                    $qb = $er->createQueryBuilder('p')                      
                      ->leftJoin('App\Entity\Farm','f','WITH','f.idFarm = p.fkFarm')
                      ->leftJoin('App\Entity\Referent','r','WITH','r.fkFarm = f.idFarm')
                      ->where('r.fkUser = :user')
                      ->orderBy('f.sequence, p.sequence')
                      ->setParameter('user', $options['user']);
                    if ($options['is_new'])//nouveau contrat : on affiche que les produits actifs des fermes actives
                    {
                      $qb->andWhere('p.isActive=1')
                        ->andWhere('f.isActive=1');  ;
                    }
                    return $qb;
                  }
              }));

    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Contract::class
        ));
    }
    
    public function configureOptions( OptionsResolver $resolver ) {
        $resolver->setDefaults( [
          'user' => null,
          'is_new' => null,
        ] );
    }
    /**
     * @return string
     */
    public function getName()
    {
        return 'amap_orderbundle_contract';
    }
}
