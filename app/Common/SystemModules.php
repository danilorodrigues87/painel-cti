<?php

namespace App\Common;

class SystemModules {

   //PERMISSÕES
    private static $permissions = [
        "Funcionários",
        "Responsáveis",
        "Leads",
        "Vouchers",
        "Categorias",
        "Trilhas",
        "Certificações",
        "Alunos",
        "Matriculas",
        "Entrada",
        "Saída",
        "Carnês",
        "Vendas",
        "Recorrente",
        "Relatórios",
        "Gerentes",
        "Empresas",
        "Diretores",
        "Escolas",
        "Contratos",
        "Laboratório",
        "Mensagens"
    ];

    public static function getPermissions(){
        return self::$permissions;
    }


/* MODELOS DE MODULOS DO MENU LATERAL
-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

//MODULO SIMPLES
 'Dashboard' => 
        [
            'label' => 'Dashboard', <-- VAI PEGAR PELO LABEL
            'link' => URL.'/painel',
            'icon' => 'fas fa-tachometer-alt' <-- ICONE DA OPÇÃO NO MENU
        ]

//MODULO COM SUBMENU
'users' => [
            'label' => 'Usuários',
            'icon' => 'fas fa-users',  <-- ICONE DA OPÇÃO NO MENU
            'subsections' => [
                'name' => 'Layouts-users',
                'icon' => 'fas fa-caret-down',
                'items' => [
                    [
                        'label' => 'Funcionários', <-- VAI PEGAR PELO LABEL
                        'link' => URL.'/painel/user'
                    ],  
                    [
                        'label' => 'Clientes',  <-- VAI PEGAR PELO LABEL
                        'link' => URL.'/painel/clientes'
                    ]           
            
                ]
            ]
        ]

-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
*/



       private static $modules = [
        
        'Dashboard' => [
            'label' => 'Dashboard',
            'link' => URL.'/painel',
            'icon' => 'fas fa-tachometer-alt'
        ],
        'users' => [
            'label' => 'Usuários',
            'icon' => 'fas fa-users',
            'subsections' => [
                'name' => 'Layouts-users',
                'icon' => 'fas fa-caret-down',
                'items' => [
                    [
                        'label' => 'Funcionários',
                        'link' => URL.'/painel/user'
                    ],  
                    [
                        'label' => 'Alunos',
                        'link' => URL.'/painel/clientes'
                    ],              
                    [
                        'label' => 'Responsáveis',
                        'link' => URL.'/painel/responsavel'
                    ]
                ]
            ]
        ],
        'pedagogico' => [
            'label' => 'Pedagógico',
            'icon' => 'fa-solid fa-graduation-cap',
            'subsections' => [
                'name' => 'Layouts-pedagogico',
                'icon' => 'fas fa-caret-down',
                'items' => [
                    [
                        'label' => 'Matriculas',
                        'link' => URL.'/painel/matriculas'
                    ],
                    [
                        'label' => 'Trilhas',
                        'link' => URL.'/painel/trilhas'
                    ],
                    [
                        'label' => 'Categorias',
                        'link' => URL.'/painel/categoria/cursos'
                    ],
                    [
                        'label' => 'Certificações',
                        'link' => URL.'/painel/certificados'
                    ]
                ]
            ]
        ],
        'Parcerias' => [
            'label' => 'Parcerias',
            'icon' => 'fa-regular fa-building',   
            'subsections' => [
                'name' => 'Layouts-parcerias',
                'icon' => 'fas fa-caret-down',
                'items' => [            
                    [
                        'label' => 'Gerentes',
                        'link' => URL.'/painel/gerentes'
                    ],
                    [
                        'label' => 'Empresas',
                        'link' => URL.'/painel/empresa'
                    ],
                    [
                        'label' => 'Diretores',
                        'link' => URL.'/painel/diretores'
                    ]
                ]
            ]
        ],
        'Suporte' => [
            'label' => 'Suporte',
            'icon' => 'fa-brands fa-whatsapp',
            'subsections' => [
                'name' => 'Layouts-Suporte',
                'icon' => 'fas fa-caret-down',
                'items' => [
                    [
                        'label' => 'Mensagens',
                        'link' => URL.'/painel/whatsappatm'
                    ]
                ]
            ]
        ],
        'Financeiro' => [
            'label' => 'Financeiro',
            'icon' => 'fa-solid fa-coins',
            'subsections' => [
                'name' => 'Layouts-Financeiro',
                'icon' => 'fas fa-caret-down',
                'items' => [
                    [
                        'label' => 'Entrada',
                        'link' => URL.'/painel/caixa/entrada'
                    ],
                    [
                        'label' => 'Saída',
                        'link' => URL.'/painel/caixa/saida'
                    ],
                    [
                        'label' => 'Carnês',
                        'link' => URL.'/painel/carnes'
                    ],
                    [
                        'label' => 'Vendas',
                        'link' => URL.'/painel/vendas'
                    ],
                    [
                        'label' => 'Relatórios',
                        'link' => URL.'/painel/caixa/relatorio'
                    ]
                ]
            ]
        ],
        'agenda' => [
            'label' => 'Agenda e Horários',
            'icon' => 'fa-regular fa-calendar-check', 
            'subsections' => [
                'name' => 'Layouts-agenda',
                'icon' => 'fas fa-caret-down',
                'items' => [
                    [
                        'label' => 'Laboratório',
                        'link' => URL.'/painel/agenda/laboratorio'
                    ]
                ]
            ]
        ],
        'config-estoque' => [
            'label' => 'Config Estoque',
            'icon' => 'fa-solid fa-folder-tree',
            'subsections' => [
                'name' => 'Layouts-config-estoque',
                'icon' => 'fas fa-caret-down',
                'items' => [
                    [
                        'label' => 'Categoria-estoque',
                        'link' => URL.'/painel/categoria/estoque'
                    ],
                    [
                        'label' => 'Unidade',
                        'link' => URL.'/painel/categoria/estoque'
                    ],
                    [
                        'label' => 'Caixa',
                        'link' => URL.'/painel/categoria/estoque'
                    ]
                ]
            ]
        ],
        'Estoque' => [
            'label' => 'Estoque',
            'icon' => 'fa-solid fa-boxes-stacked',
            'subsections' => [
                'name' => 'Layouts-estoque',
                'icon' => 'fas fa-caret-down',
                'items' => [
                    [
                        'label' => 'Categoria-estoque',
                        'link' => URL.'/painel/categoria/estoque'
                    ],
                    [
                        'label' => 'Unidade',
                        'link' => URL.'/painel/categoria/estoque'
                    ],
                    [
                        'label' => 'Caixa',
                        'link' => URL.'/painel/categoria/estoque'
                    ]
                ]
            ]
        ],
        'Configurações' => [
            'label' => 'Configurações',
            'icon' => 'fa-solid fa-sliders',
            'subsections' => [
                'name' => 'Layouts-config',
                'icon' => 'fas fa-caret-down',
                'items' => [
                    [
                        'label' => 'Contratos',
                        'link' => URL.'/painel/contratos'
                    ],
                    [
                        'label' => 'Configurações',
                        'link' => URL.'/painel/config'
                    ]
                ]
            ]
        ],
        'Termos de Uso' => [
            'label' => 'Termos de Uso',
            'link' => URL.'/painel/termos-de-uso',
            'icon' => 'fa-solid fa-file-circle-check'
        ]

    ];

    public static function getModules(){

        return Self::$modules;
    }

}
