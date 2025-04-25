<template>
        <a v-if="showNewElement && !isAuthenticated" class="elemento-adicional" href="profile/affiliate">
        <h1>{{ setting.software_description }}</h1>
        <button @click="$router.push('/profile/affiliate')" class="botao-adicional">Resgatar</button>
    </a>
    <a v-if="showNewElement && isAuthenticated" class="elemento-adicional" href="profile/affiliate">
        <h1>{{ setting.software_description2 }}</h1>
        <button @click="$router.push('/profile/affiliate')" class="botao-adicional">Resgatar</button>
    </a>

    <nav class="fixed navbar z-45 w-full navtop-color border-none custom-box-shadow translucent-navbar"
         :style="{ top: showNewElement ? '30px' : '0' }">
        
<div v-if="!sumirmodal" id="modalElDeposit" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full overflow-x-hidden overflow-y-auto md:inset-0 h-screen md:h-[calc(100%-1rem)] max-h-full translucent-menu">
    <div class="relative w-full max-w-2xl max-h-full mx-auto mt-4"> <!-- Adiciona margin-top e centraliza horizontalmente -->
        <div class="flex flex-col md:justify-between px-6 pb-8 my-auto w-full">
            <div class="flex items-center justify-between modal-header mt-2 w-full">
                <div class="flex-1 text-center w-full" > <!-- Centraliza o texto lateralmente -->
                    <h1 class="font-bold text-xl" style="color: #fff">Depositar</h1>
                </div>
                <div class="absolute right-6 top-2"> <!-- Alinha o botão "Fechar" com o título -->
                    <button @click.prevent="toggleModalDeposit" class="text-white hover:text-white">
                        Fechar
                    </button>
                </div>
            </div>
            <hr class="w-full border-t border-gray-300 my-2 w-full"> <!-- Linha fina logo abaixo -->
            <img :src="`/storage/`+setting.software_girosbanner" class="rounded bonus-icon w-full mt-2" alt="Bonus Icon"> <!-- Espaço ajustado abaixo da linha -->
            <DepositWidget @payment-completed="toggleModalDeposit"/> 
        </div>
    </div>
</div>
 <div v-if="!sumirmodal2" id="modalElSaque" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full overflow-x-hidden overflow-y-auto md:inset-0 h-screen md:h-[calc(100%-1rem)] max-h-full translucent-menu">
    <div class="relative w-full max-w-2xl max-h-full mx-auto mt-4"> <!-- Adiciona margin-top e centraliza horizontalmente -->
        <div class="flex flex-col md:justify-between px-6 pb-8 my-auto w-full">
            <div class="flex items-center justify-between modal-header mt-2 w-full">
                <div class="flex-1 text-center w-full" > <!-- Centraliza o texto lateralmente -->
                    <h1 class="font-bold text-xl" style="color: #fff">Sacar</h1>
                </div>
                <div class="absolute right-6 top-2"> <!-- Alinha o botão "Fechar" com o título -->
                    <button @click.prevent="toggleModalSaque" class="text-white hover:text-white">
                        Fechar
                    </button>
                </div>
            </div>
            <hr class="w-full border-t border-gray-300 my-2 w-full"> <!-- Linha fina logo abaixo -->
                        <SaqueWidget/>

        </div>
    </div>
</div>
        
        <div class="px-3 lg:px-5 lg:pl-3 nav-menu">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-start py-3">
                    <a v-if="setting" href="/" class="flex ml-1 md:mr-24">
                        <div class="hidden sm:block">
                            <img :src="`/storage/`+setting.software_logo_black" alt="" class="h-8 mr-1 block dark:hidden " />
                            <img :src="`/storage/`+setting.software_logo_white" alt="" class="h-8 mr-1 hidden dark:block" />
                        </div>
                        <div class="block sm:hidden">
                            <img :src="`/storage/`+setting.software_logo_white" class="mt-2 h-8 mr-1" alt="" />
                        </div>
                    </a>
                </div>
                <div class="hidden md:block"></div>
                <div v-if="!simple" class="flex items-center py-3">
                    <div v-if="!isAuthenticated" class="flex ml-5" v-show="isMobile">
                        <div class="relative inline-block mr-1">
    <button class="ui-button-depositorapido2 relative" @click.prevent="$router.push('register')">
<span class="neon-text">Cadastrar</span>
</button>
    <div class="pix-container absolute">
        <i class="fa-solid fa-gift pix-icon2"></i>
        <span class="pix-text">100%</span>
    </div>
</div>
                        <button @click.prevent="$router.push('login')" class="ui-button-blue3 ml-1 mr-1 rounded">{{ $t('Log in') }}</button>
                    </div>
                    <div v-if="!isAuthenticated" class="flex ml-5" v-show="!isMobile">
                        <div class="relative inline-block mr-1">
    <button class="ui-button-depositorapido2 relative" @click.prevent="registerToggle">
<span class="neon-text">Cadastrar</span>
</button>
    <div class="pix-container absolute">
        <i class="fa-solid fa-gift pix-icon2"></i>
        <span class="pix-text">100%</span>
    </div>
</div>
                        <button @click.prevent="loginToggle" class="ui-button-blue3 ml-1 mr-1 rounded">{{ $t('Log in') }}</button>
                    </div>
                    <div v-if="isAuthenticated" class="flex items-center">
                        <div class="flex items-center ml-4">
              
<div class="relative inline-block mr-1">
    <button class="ui-button-depositorapido2 relative" @click.prevent="toggleModalDeposit">
<span class="neon-text">Depósito</span>
</button>
    <div class="pix-container absolute">
        <i class="fa-brands fa-pix pix-icon"></i>
        <span class="pix-text">PIX</span>
    </div>
</div>

  <div class="flex items-center">
    <WalletBalance />
    <div>
    <button type="button" class="mr-1 flex text-sm bg-gray-800 rounded-full relative" aria-expanded="false" data-dropdown-toggle="dropdown-profile">
      <span class="sr-only">Open user menu</span>
      <div class="w-8 h-8 overflow-hidden rounded-full">
        <img class="object-cover w-full h-full" :src="userData?.avatar ? '/storage/'+userData.avatar : '/assets/images/profile.jpg'" alt="">
      </div>
      <!-- Badge -->
      <span v-if="showBadge" class="absolute top-0 right-0 transform translate-x-2 -translate-y-1">
        <span class="rounded-full bg-red-700 text-white px-1 py-0.5 text-xs flex items-center justify-center" style="width: 12px; height: 12px; border: 1px solid #fff;">
1
</span>
      </span>
    </button>
    </div>
  </div>
                            

  
<div  class="w-full  z-999 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded shadow dark:bg-gray-700 dark:divide-gray-600 translucent-menu" id="dropdown-profile">

      <!-- Conteúdo do seu menu aqui -->
  <div v-if="!wallet2" class="flex flex-col items-center justify-center w-full h-full border border-gray-300 rounded bg-transparent p-4">

    <!-- Bola sem fundo com as iniciais do nome dentro -->
    <div v-if="!wallet2" class="flex items-center justify-center w-16 h-16 custom-border rounded-full bg-transparent mb-2">
      <span class="text-xl font-bold custom-text">{{ getInitials(userData?.name) }}</span>
    </div>

    <!-- Nome completo abaixo da bola -->
    <p class="text-white dark:text-white text-xl font-bold">{{ userData?.name }}</p>

  </div>

 <div  class="flex flex-col items-center justify-center w-full h-full border border-gray-300 custom-bg p-4 mt-2">
     
  <button v-if="wallet2 && bonus" class="flex items-center justify-center w-full h-full border border-gray-300 rounded custom-bg p-2 mb-2" @click.prevent="toggleBonus">
    <i class="fa-regular fa-arrow-left mr-2"></i>
    <p class="text-white text-lg font-bold">Voltar</p>
  </button>
    
    <div v-if="wallet2 && bonus" class="flex flex-col items-center justify-center w-full h-full border border-gray-300 rounded bg-transparent p-4">
        
    <div v-if="showBadge " class="items-center justify-center text-center">
        
    <div class="mt-2">
        
    <Cashback/>
    </div>
    </div>
    
    <div v-if="!showBadge" class="flex flex-col items-center justify-center w-full h-full border border-gray-300 rounded bg-transparent p-4">
    <p class="text-gold dark:text-gold text-xl font-bold">🥺 Poxa... nenhum bônus disponivel no momento. </p>
    <p class="text-gold dark:text-gold text-xl font-bold">😉 Volte Amanhã...</p>

           </div>
        <div class="w-full flex justify-center mt-4">
      <Cupom />
    </div>

    </div>

  <button v-if="wallet2 && !bonus" class="flex items-center justify-center w-full h-full border border-gray-300 rounded custom-bg p-2 mb-2" @click.prevent="togglePerfil">
    <i class="fa-regular fa-arrow-left mr-2"></i>
    <p class="text-white text-lg font-bold">Voltar</p>
  </button>
  
  <div v-if="dep" class="mt-2 flex flex-col items-center justify-center w-full h-full border border-gray-300 rounded custom-bg p-2 mb-2">
    <p class="text-white text-lg font-bold">Histórico De Depositos!</p>
                <div v-if="deposits && deposits.data.length < 0" class="text-gray-900 bg-orange-200 border border-orange-500 rounded-lg p-4">
            <p class="text-lg font-semibold">Atenção:</p>
            <p>Você ainda não efetuou nenhuma transação.</p>
        </div>
  <div v-if="deposits && wallet && !bonus" class="w-full mt-3">
    <div class="relative overflow-x-auto">
      <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-white uppercase bg-gray-700">
          <tr>
            <th scope="col" class="px-3 py-2 text-gold">{{ $t('Value') }}</th>
            <th scope="col" class="px-3 py-2 text-white">{{ $t('Status') }}</th>
            <th scope="col" class="px-3 py-2 text-white">{{ $t('Date') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(deposit, index) in sortedAndLimitedDeposits" :key="index" class="bg-[#3c3f42] border-b border-gray-700">
            <td class="px-3 py-2 text-gold">{{ state.currencyFormat(parseFloat(deposit.amount), deposit.currency) }}</td>
            <td class="px-3 py-2">
              <span v-if="deposit.status === 1" class="bg-gold text-black text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gold dark:text-black">{{ $t('Concluded') }}</span>
              <span v-if="deposit.status === 0" class="bg-gold text-black text-xs font-medium px-2.5 py-0.5 rounded dark:bg-yellow-900 dark:text-black">{{ $t('Pending') }}</span>
            </td>
            <td class="px-3 py-2 text-white">{{ deposit.dateHumanReadable }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  </div>

  <div v-if="saq && !bonus" class="mt-2 flex flex-col items-center justify-center w-full h-full border border-gray-300 rounded custom-bg p-2 mb-2">
    <p class="text-white text-lg font-bold">Histórico De Saques!</p>
    
                <div v-if="withdraws && withdraws.data.length < 0" class="text-gray-900 bg-orange-200 border border-orange-500 rounded-lg p-4">
            <p class="text-lg font-semibold">Atenção:</p>
            <p>Você ainda não efetuou nenhuma transação.</p>
        </div>
        
  <div v-if="withdraws && wallet" class="w-full mt-3">
    <div class="relative overflow-x-auto">
      <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-white uppercase bg-gray-700">
          <tr>
            <th scope="col" class="px-3 py-2 text-gold">{{ $t('Value') }}</th>
            <th scope="col" class="px-3 py-2 text-white">{{ $t('Status') }}</th>
            <th scope="col" class="px-3 py-2 text-white">{{ $t('Date') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(withdraw, index) in sortedAndLimitedWithdraws" :key="index" class="bg-[#3c3f42] border-b border-gray-700">
            <td class="px-3 py-2 text-gold">{{ state.currencyFormat(parseFloat(withdraw.amount), withdraw.currency) }}</td>
            <td class="px-3 py-2">
              <span v-if="withdraw.status === 1" class="bg-gold text-black text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gold dark:text-black">{{ $t('Concluded') }}</span>
              <span v-if="withdraw.status === 0" class="bg-gold text-black text-xs font-medium px-2.5 py-0.5 rounded dark:bg-yellow-900 dark:text-black">{{ $t('Pending') }}</span>
            </td>
            <td class="px-3 py-2">{{ withdraw.dateHumanReadable }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  
  
  </div>


                        <div v-if="wallet2 && !historico" class="mt-3 mb-2 flex gap-4">
                            <button @click.prevent="setDepositarFalse" class="ui-button-custom6">Depositar</button>
                            <button @click.prevent="setDepositarTrue" class="ui-button-custom7">Sacar</button>
                
                       </div>
                       
    <!-- Retângulo de saldo da conta -->
  <div v-if="!historico && !bonus" class="mt-2 flex flex-col items-center justify-center w-full h-full border border-gray-300 rounded custom-bg p-2 mb-2">
    <p class="text-white text-lg font-bold">Saldo Saque</p>
    <WalletMenu />
  </div>

    <!-- Retângulo de saldo de bônus -->
  <div v-if="!historico && !wallet2" class="mt-2 flex flex-col items-center justify-center w-full h-full border border-gray-300  rounded custom-bg p-2 mb-2">
    <p class="text-white text-lg font-bold">Saldo Bônus</p>
    <WalletMenu2 />
  </div>

    <!-- Botão de depositar -->
  <button v-if="!depositar && !historico" class="ui-button-custom-deposito mt-1" @click.prevent="toggleModalDeposit"> 
    <i class="fa-solid fa-money-bill mr-2"></i>
    Depositar
  </button>
    <button v-if="depositar && wallet2 && !historico" class="ui-button-custom-deposito mt-1" @click.prevent="toggleModalSaque">
    <i class="fa-solid fa-wallet mr-2"></i>
    Saque
  </button>
  
      <p v-if="wallet2 && !historico" class="mt-4 text-white text-lg font-bold">Configurações da Carteira:</p>
  <button v-if="!depositar && wallet2 && !historico" class="mt-2 flex items-center justify-center w-full h-full border border-gray-300 rounded custom-bg p-2 mb-2" @click.prevent="setHistoricoDeposito">
    <i class="fa-regular fa-money-bill-trend-up mr-2"></i>
    <p class="text-white text-lg font-bold">Histórico de Depositos</p>
  </button>
  
    <button v-if="depositar && wallet2 && !historico" class="mt-2 flex items-center justify-center w-full h-full border border-gray-300 rounded custom-bg p-2 mb-2" @click.prevent="setHistoricoSaque">
    <i class="fa-regular fa-money-bill-trend-up mr-2"></i>
    <p class="text-white text-lg font-bold">Histórico de Saques</p>
  </button>
 </div>


 <div v-if="!wallet2 && !historico" class="flex flex-col items-center justify-center w-full h-full border border-gray-300  custom-bg p-4 ">

    <!-- Retângulo de saldo da conta -->
  <button class="flex items-center justify-center w-full h-full border border-gray-300 rounded custom-bg p-2 mb-1  mt-2" @click.prevent="toggleBonus">
    <i class="fa-duotone fa-gift mr-2"></i>
    <p class="text-white text-lg font-bold mr-2">Bônus</p>
    <span v-if="showBadge" class="rounded-full bg-red-700 text-white px-1 py-0.5 text-xs flex items-center justify-center" style="width: 20px; height: 20px; border: 1px solid #fff;">
            1
        </span>
  </button>

  <!-- Retângulo de saldo de bônus -->
  <button class="flex items-center justify-center w-full h-full border border-gray-300 rounded custom-bg p-2 mb-2" @click.prevent="togglePerfil">
    <i class="fa-regular fa-money-bill-trend-up mr-2"></i>
    <p class="text-white text-lg font-bold">Carteira</p>
  </button>

 </div>
  

  
</div>



      <!-- Conteúdo do seu menu aqui -->





                            <div class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded shadow dark:bg-gray-700 dark:divide-gray-600" id="dropdown-user">
                                <ul class="py-1" role="none">

<li class="relative">
    <RouterLink :to="{ name: 'profileAffiliate' }" active-class="profile-menu-active" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white flex items-center">
        <span class="flex items-center">
            <i class="fa-duotone fa-gift mr-4"></i> <!-- Adicionado margin-right para ajustar o espaçamento -->
            <span class="mr-2">Bônus</span>
        </span>
        <span v-if="showBadge" class="rounded-full bg-red-700 text-white px-1 py-0.5 text-xs flex items-center justify-center" style="width: 16px; height: 16px; border: 1px solid #fff;">
            1
        </span>
    </RouterLink>
</li>


                                   <li>
                                        <RouterLink :to="{ name: 'profileAffiliate' }" active-class="profile-menu-active" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white">
                                            <span class="w-8 h-8 mr-3">
                                                <i class="fa-duotone fa-people-group"></i>
                                            </span>
                                            Indique e Ganhe!
                                        </RouterLink>
                                    </li>
                                    <li>
                                        <a @click.prevent="logoutAccount" href="#" class="block px-4 py-2 text-sm text-red hover:bg-gray-100 dark:text-red dark:hover:bg-gray-600 dark:hover:text-red" role="menuitem">
                                             <span class="w-8 h-8 mr-3">
                                               <i class="fa-duotone fa-right-from-bracket"></i>
                                            </span>
                                            {{ $t('Sign out') }}
                                        </a>
                                    </li>
                                </ul>
                        </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <transition name="fade">
            <div v-if="showSearchMenu" class="fixed top-0 left-0 right-0 bottom-0 flex items-center justify-center ">
                <div @click="toggleSearch" class="absolute inset-0 carousel_banners opacity-50 cursor-pointer"></div>

                <!-- Start searchbar action -->
                <div class="search-menu p-4 sm:ml-64">

                    <div class="mb-5 w-full">
                        <div class="md:w-4/6 2xl:w-4/6 mx-auto">
                            <div class="flex flex-col">
                                <div class="relative w-full">
                                    <input type="search"
                                           v-model="searchTerm"
                                           class="block dark:focus:border-green-500 p-2.5 w-full z-20 text-sm text-gray-900 input-color-primary rounded-lg border focus:outline-none dark:border-gray-600 dark:placeholder-gray-400 dark:text-white "
                                           placeholder="Nome do jogo | Provedor"
                                           required>

                                    <button v-if="searchTerm.length > 0" @click.prevent="clearData" type="button" class="absolute top-0 end-0 h-full p-2.5 text-sm font-medium text-white rounded-e-lg dark:bg-[#1C1E22] ">
                                        <span class="">Recusar</span>
                                    </button>
                                </div>
                                <div class="text-center mt-4">
                                    <p>A pesquisa requer pelo menos 3 caracteres</p>
                                </div>
                            </div>

                            <div v-if="!isLoadingSearch" class="mt-8 grid grid-cols-3 md:grid-cols-6 gap-4 py-5">
                                <CassinoGameCard
                                    v-if="games"
                                    v-for="(game, index) in games?.data"
                                    :index="index"
                                    :title="game.game_name"
                                    :cover="game.cover"
                                    :gamecode="game.game_code"
                                    :type="game.distribution"
                                    :game="game"
                                />
                            </div>
                            <div v-else class="relative items-center block max-w-sm p-6 bg-white border border-gray-100 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-800 dark:hover:bg-gray-700">
                                <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white opacity-20">Noteworthy technology acquisitions 2021</h5>
                                <p class="font-normal text-gray-700 dark:text-gray-400 opacity-20">Here are the biggest enterprise technology acquisitions of 2021 so far, in reverse chronological order.</p>
                                <div role="status" class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2">
                                    <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End searchbar action -->


            </div>
        </transition>

    </nav>

    <div id="modalElAuth" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full overflow-x-hidden overflow-y-auto md:inset-0 h-screen md:h-[calc(100%-1rem)] max-h-full translucent-menu">
        <div class="relative w-full max-w-3xl max-h-full bg-base rounded-lg shadow-lg">
            <div class="flex md:justify-between">

                <div class="w-full relative p-5">
                    <div v-if="isLoadingLogin" class="absolute top-0 left-0 right-0 bottom-0 bg-white/70 dark:bg-gray-800/70 z-[999]">
                        <div role="status" class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2">
                            <svg aria-hidden="true" class="w-10 h-10 mr-2 text-gray-200 animate-spin dark:text-gray-600 fill-green-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>

                    <form @submit.prevent="loginSubmit" method="post" action="" class="">
                        <div class="flex justify-between mb-6">

                         <img  :src="`/storage/`+setting.software_login" class="mt-2 logo w-full" alt="Logo do Site">

                        </div>

<div>
    <div class="relative mb-3">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                <i class="fa-regular fa-envelope text-success-emphasis"></i>
                            </div>
      <input 
        required 
        type="text" 
        v-model="loginForm.email" 
        name="email" 
        class="input-group border-4 border-gray-300 focus:border-gray-400 focus:ring-0 placeholder-gray-500 text-base font-semibold"
        :placeholder="$t('Enter email or phone')"
      >
    </div>

    <div class="relative mb-6">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                <i class="fa-regular fa-lock text-success-emphasis"></i>
                            </div>
      <input 
        required 
        :type="typeInputPassword"
        v-model="loginForm.password"
        name="password"
        class="input-group border-4 border-gray-300 focus:border-gray-400 focus:ring-0 placeholder-gray-500 text-base font-semibold pr-[40px]"
        :placeholder="$t('Type the password')"
      >
      <button 
        type="button" 
        @click.prevent="togglePassword" 
        class="absolute inset-y-0 right-0 flex items-center pr-3.5"
      >
        <i v-if="typeInputPassword === 'password'" class="fa-regular fa-eye"></i>
        <i v-if="typeInputPassword === 'text'" class="fa-sharp fa-regular fa-eye-slash"></i>
      </button>
    </div>
  </div>

                        <div class="mt-3 w-full">
                            <button type="submit" class="ui-button-blue rounded w-full mb-4">
                                {{ $t('Log in') }}
                            </button>
                        </div>
    <div class="flex justify-between items-center mb-6">
      <p class="text-sm text-gray-500 dark:text-gray-300">
        Novo por aqui? <a href="" @click.prevent="hideLoginShowRegisterToggle"><strong>Criar conta</strong></a>
      </p>
      <a href="/forgot-password" class="text-white text-sm">{{ $t('Forgot password') }}</a>
    </div>


                    </form>


                </div>
            </div>
        </div>
    </div>

    <div id="modalElRegister" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full overflow-x-hidden overflow-y-auto md:inset-0 h-screen md:h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-3xl max-h-full bg-base rounded-lg shadow-lg">
            <div v-if="isLoadingRegister" class="absolute top-0 left-0 right-0 bottom-0 bg-white/70 dark:bg-gray-800/70 z-[999]">
                <div role="status" class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2">
                    <svg aria-hidden="true" class="w-10 h-10 mr-2 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
                    <span class="sr-only">Loading...</span>
                </div>
            </div>

            <div class="flex md:justify-between h-full">

                <div class="w-full relative p-5 m-auto">
                    <form @submit.prevent="registerSubmit" method="post" action="" class="">
                        <div class="flex justify-between mb-6">

                        </div>

                        <div class="relative mb-3">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                <i class="fa-regular fa-user text-success-emphasis"></i>
                            </div>
                            <input type="text"
                                   name="name"
                                   v-model="registerForm.name"
                                   class="input-group"
                                   :placeholder="$t('Enter name')"
                                   required
                            >
                        </div>

                        <div class="relative mb-3">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                <i class="fa-regular fa-envelope text-success-emphasis"></i>
                            </div>
                            <input type="email"
                                   name="email"
                                   v-model="registerForm.email"
                                   class="input-group"
                                   :placeholder="$t('Enter email or phone')"
                                   required
                            >
                        </div>

                        <div class="relative mb-3">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                <i class="fa-light fa-address-card text-success-emphasis"></i>
                            </div>
                            <input type="text"
                                   name="cpf"
                                   v-model="registerForm.cpf"
                                   class="input-group"
                                   :placeholder="$t('Enter cpf')"
                                   required
                            >
                        </div>

                        <div class="relative mb-3">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                <i class="fa-regular fa-lock text-success-emphasis"></i>
                            </div>
                            <input :type="typeInputPassword"
                                   name="password"
                                   v-model="registerForm.password"
                                   class="input-group pr-[40px]"
                                   :placeholder="$t('Type the password')"
                                   required
                            >
                            <button type="button" @click.prevent="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3.5 ">
                                <i v-if="typeInputPassword === 'password'" class="fa-regular fa-eye"></i>
                                <i v-if="typeInputPassword === 'text'" class="fa-sharp fa-regular fa-eye-slash"></i>
                            </button>
                        </div>

                        <div class="relative mb-3">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                <i class="fa-regular fa-lock text-success-emphasis"></i>
                            </div>
                            <input :type="typeInputPassword"
                                   name="password_confirmation"
                                   v-model="registerForm.password_confirmation"
                                   class="input-group pr-[40px]"
                                   :placeholder="$t('Confirm the Password')"
                                   required
                            >
                            <button type="button" @click.prevent="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3.5">
                                <i v-if="typeInputPassword === 'password'" class="fa-regular fa-eye"></i>
                                <i v-if="typeInputPassword === 'text'" class="fa-sharp fa-regular fa-eye-slash"></i>
                            </button>
                        </div>
                        <div class="relative mb-3">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                <i class="fa-regular fa-phone"></i>
                            </div>
                            <input type="text"
                                   name="phone"
                                   v-maska
                                   data-maska="[
                                    '(##) #####-####'
                                  ]"
                                   v-model="registerForm.phone"
                                   class="input-group"
                                   :placeholder="$t('Enter your phone')"
                                   required
                            >
                        </div>

                        <div class="mb-3 mt-5" style="display: none;">
                            <button @click.prevent="isReferral = !isReferral" type="button" class="flex justify-between w-full">
                                <p>{{ $t('Enter Referral/Promo Code') }}</p>
                                <div class="">
                                    <i v-if="isReferral" class="fa-solid fa-chevron-up"></i>
                                    <i v-if="!isReferral" class="fa-solid fa-chevron-down"></i>
                                </div>
                            </button>

                            <div v-if="isReferral" class="relative mb-3 mt-1" style="display: none;">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                    <i class="fa-regular fa-user text-success-emphasis"></i>
                                </div>
                                <input type="text" name="name" v-model="registerForm.reference_code" class="input-group" :placeholder="$t('Code')">
                            </div>
                        </div>

                        <hr class="mb-3 mt-2 dark:border-gray-600">

                                    <div class="mb-3 mt-11">
                                        <div class="flex">
                                            <input id="term-a" v-model="registerForm.term_a" name="term_a" required type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                            <label for="term-a" class="ml-2 text-sm font-medium text-left text-gray-900 dark:text-gray-300">{{ $t('Eu confirmo ter mais de 18 anos e Aceito os termos de uso!') }}</label>
                                        </div>
                                    </div>

                                    <div class="mb-3" style="display: none;">
                                        <div class="flex items-center">
                                            <input id="term-agreement" v-model="registerForm.agreement" name="term_b" required type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                            <label for="term-agreement" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ $t('I agree with the') }} <a href="#" class="text-primary hover:underline">{{ $t('terms and conditions') }}</a>.</label>
                                        </div>
                                    </div>

                        <div class="mt-5 w-full">
                            <button type="submit" class="ui-button-blue rounded w-full mb-3">
                                {{ $t('Register') }}
                            </button>
                        </div>
                    </form>


                </div>
            </div>
        </div>
    </div>

    <div id="modalProfileEl" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full overflow-x-hidden overflow-y-auto md:inset-0 h-screen md:h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-2xl md:max-w-lg max-h-full bg-white dark:bg-gray-900 rounded-lg shadow-lg">
            <div v-if="!isLoadingProfile" class="flex flex-col">

                <!-- PROFILE HEADER -->
                <div class="flex justify-between w-full p-4">
                    <h1 class="text-2xl font-bold">{{ $t('User data') }}</h1>
                    <button @click.prevent="profileToggle" type="button" class="text-2xl">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- PROFILE BODY -->
                <div v-if="profileUser != null" class="flex flex-col w-full p-4">

                    <!-- PROFILE INFO -->
                    <div class="flex items-center self-center justify-between w-full">
                        <button @click.prevent="like(profileUser.id)" type="button" class="heart">
                            <i class="fa-solid fa-heart"></i>
                            <span class="ml-2">{{ profileUser.totalLikes }}</span>
                        </button>
                        <div class="text-center flex flex-col justify-center self-center items-center">
                            <div class="relative">
                                <img class="w-24 h-246 p-1 rounded-full ring-2 ring-gray-300 dark:ring-gray-500"
                                     :src="avatarUrl"
                                     alt="">
                                <input ref="fileInput" type="file" style="display: none" @change="handleFileChange">
                                <button @click="openFileInput" type="button" class="absolute bottom-0 right-0 text-3xl">
                                    <i class="fa-duotone fa-image"></i>
                                </button>
                            </div>
                            <div class="relative">
                                <input @change.prevent="updateName" v-model="profileName" type="text" :readonly="!readonly" class="mt-4 appearance-none border border-gray-300 rounded-md p-2 bg-transparent border-none text-center" :placeholder="profileName" >
                            </div>
                        </div>
                        <div class="">
                            <button @click.prevent="readonly = !readonly" type="button" class="bg-gray-200 hover:bg-gray-400 dark:bg-gray-600 hover:dark:bg-gray-700 w-10 h-10  rounded">
                                <i v-if="!readonly" class="fa-sharp fa-light fa-pen"></i>
                                <i v-if="readonly" class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mt-3 shadow flex flex-col bg-gray-100 dark:bg-gray-900 rounded-lg">
                        <div class="flex justify-between px-4 pt-4">
                            <h1><span class="mr-2"><i class="fa-solid fa-chart-mixed"></i></span> {{ $t('Statistics') }}</h1>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="bg-gray-200 dark:bg-gray-700 text-center p-4">
                                    <p class="text-[12px]">{{ $t('Total winnings') }}</p>
                                    <p class="text-2xl font-bold">
                                        {{ totalEarnings }}
                                    </p>
                                </div>
                                <div class="bg-gray-200 dark:bg-gray-700 text-center p-4">
                                    <p class="text-[12px]">{{ $t('Total bets') }}</p>
                                    <p class="text-2xl font-bold">{{ totalBets }}</p>
                                </div>
                                <div class="bg-gray-200 dark:bg-gray-700 text-center p-4">
                                    <p class="text-[12px]">{{ $t('Total bet') }}</p>
                                    <p class="text-2xl font-bold">{{ sumBets }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="py-3 text-center">
                        <p>ingressou em {{ profileUser.dateHumanReadable }}</p>
                    </div>

                </div>
            </div>
            <div v-if="isLoadingProfile" class="flex flex-col w-full h-full">
                <div role="status" class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2">
                    <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
                    <span class="sr-only">{{ $t('Loading') }}...</span>
                </div>
            </div>
        </div>
    </div>
    
        <div v-if="isAuthenticated" id="modalElpresente" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full overflow-x-hidden overflow-y-auto md:inset-0 h-screen md:h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-3xl max-h-full bg-base rounded-lg shadow-lg">
            <div class="flex md:justify-between">

                <div class="w-full relative p-5">
                    <div v-if="isLoadingLogin" class="absolute top-0 left-0 right-0 bottom-0 bg-white/70 dark:bg-gray-800/70 z-[999]">
                        <div role="status" class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2">
                            <svg aria-hidden="true" class="w-10 h-10 mr-2 text-gray-200 animate-spin dark:text-gray-600 fill-green-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

                </div>

<div class="text-center" v-if="pwa">
    
    <h5 class="mb-3 font-bold text-xl">Obrigado por baixar nosso super APP !!</h5>
    <h2 class="mb-3 font-bold text-xl">Pressione o botão para ativar seus GIROS-GRÁTIS</h2>
    
    <button @click.prevent="loginToggle" class="ui-button-blue rounded mx-auto">ATIVAR GIROS-GRÁTIS</button>
</div>

             
        



</template>
<script>
import { RouterLink, useRoute } from "vue-router";
import { sidebarStore } from "@/Stores/SideBarStore.js";
import { Modal } from 'flowbite';
import { useAuthStore } from "@/Stores/Auth.js";
import { useToast } from "vue-toastification";
import { useRouter } from 'vue-router';

import DropdownDarkLight from "@/Components/UI/DropdownDarkLight.vue";
import LanguageSelector from "@/Components/UI/LanguageSelector.vue";
import WalletBalance from "@/Components/UI/WalletBalanceG.vue";
import HttpApi from "@/Services/HttpApi.js";
import WalletMenu from "@/Components/UI/WalletMenu.vue";
import WalletMenu2 from "@/Components/UI/WalletMenu2.vue";
import MakeDeposit2 from "@/Components/UI/MakeDeposit2.vue";

import {useSettingStore} from "@/Stores/SettingStore.js";
import {searchGameStore} from "@/Stores/SearchGameStore.js";
import CassinoGameCard from "@/Pages/Cassino/Components/CassinoGameCard.vue";
    import DepositWidget from "@/Components/Widgets/DepositWidget.vue";
    import SaqueWidget from "@/Components/Widgets/SaqueWidget.vue";
    import Cashback from "@/Components/UI/Cashback.vue";
    import Cupom from "@/Components/UI/Cupom.vue";

export default {
    props: ['simple'],
    components: {CassinoGameCard, MakeDeposit2, WalletBalance, WalletMenu, WalletMenu2, LanguageSelector, DropdownDarkLight, RouterLink, DepositWidget, SaqueWidget, Cashback, Cupom },
    data() {
        return {
            showNewElement: true,
                sumirmodal: false,
                sumirmodal2: false,

            wallet2: false,
            depositar: false,
            historico: false,
            dep: false,
            saq: false,
            bonus: false,
            wallet: {},
            withdraws: null,
            deposits: null,
            
            isLoadingLogin: false,
            isLoadingRegister: false,
            isReferral: false,
            modalAuth: null,
            modalRegister: null,
            modalProfile: null,
            modalElpresente: null,

            typeInputPassword: 'password',
            readonly: false,
            profileUser: null,
            loginForm: {
                email: '',
                password: '',
            },
            registerForm: {
                name: '',
                email: '',
                cpf: '',
                password: '',
                password_confirmation: '',
                reference_code: '',
                term_a: true,
                agreement: true,
            },
            avatarUrl: '/assets/images/profile.jpg',
            isLoadingProfile: false,
            profileName: '',
            sumBets: 0,
            totalBets: 0,
            totalEarnings: 0,
            showSearchMenu: false,
            games: null,
            searchTerm: '',
            isLoadingSearch: true,
            showBadge: false,
            dropdownOpen: false,
            pwa: false,
            isMobile: false,
            setting: null,
            custom: null,
        }
    },
    setup() {
        const router = useRouter();

        return {
            router,
        };
    },
    computed: {
            walletInfo() {
      return this.wallet ? this.wallet.cashbackliquido : 0;
    },
        searchGameDataStore() {
            return searchGameStore();
        },
        searchGameMenu() {
            const search = searchGameStore();
            return search.getSearchGameStatus;
        },
        sidebarMenuStore() {
            return sidebarStore();
        },
        isAuthenticated() {
            const authStore = useAuthStore();
            return authStore.isAuth;
        },
        userData() {
            const authStore = useAuthStore();
            return authStore.user;
        },
        setting() {
            const authStore = useSettingStore();
            return authStore.setting;
        },
            sortedAndLimitedDeposits() {
      return this.deposits.data
        .slice()
        .sort((a, b) => new Date(b.date) - new Date(a.date)) // Ordenar por data
        .slice(0, 10); // Limitar para 10 itens
    },
    
        sortedAndLimitedWithdraws() {
      return this.withdraws.data
        .slice()
        .sort((a, b) => new Date(b.date) - new Date(a.date)) // Ordenar por data
        .slice(0, 10); // Limitar para 10 itens
    }
    },
    unmounted() {
    window.removeEventListener('resize', this.checkIfMobile);

    },
    mounted() {
    this.checkIfMobile();
    window.addEventListener('resize', this.checkIfMobile);
        /*
        * $targetEl: required
        * options: optional
        */
        this.modalProfile = new Modal(document.getElementById('modalProfileEl'), {
            placement: 'center',
            backdrop: 'dynamic',
            backdropClasses: 'bg-gray-700 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40',
            closable: false,
            onHide: () => {

            },
            onShow: () => {

            },
            onToggle: () => {

            }
        });

        /*
        * $targetEl: required
        * options: optional
        */
        this.modalAuth = new Modal(document.getElementById('modalElAuth'), {
            placement: 'center',
            backdrop: 'dynamic',
            backdropClasses: 'bg-gray-700 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40',
            closable: false,
            onHide: () => {

            },
            onShow: () => {

            },
            onToggle: () => {

            }
        });

        /*
       * $targetEl: required
       * options: optional
       */
        this.modalRegister = new Modal(document.getElementById('modalElRegister'), {
            placement: 'center',
            backdrop: 'dynamic',
            backdropClasses: 'bg-gray-700 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40',
            closable: false,
            onHide: () => {

            },
            onShow: () => {

            },
            onToggle: () => {

            }
        });
        
        this.modalElpresente = new Modal(document.getElementById('modalElpresente'), {
            placement: 'center',
            backdrop: 'dynamic',
            backdropClasses: 'bg-gray-700 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40',
            closable: false,
            onHide: () => {

            },
            onShow: () => {

            },
            onToggle: () => {

            }
        });
        
            this.modalDeposit = new Modal(document.getElementById('modalElDeposit'), {
                 placement: 'top-center', // Altere isso para 'top-center' para abrir para cima
                backdrop: 'dynamic',
                backdropClasses: 'bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-40',
                closable: true,
                onHide: () => {
                    this.paymentType = null;
                },
                onShow: () => {

                },
                onToggle: () => {

                },
            });
            this.modalSaque = new Modal(document.getElementById('modalElSaque'), {
                 placement: 'top-center', // Altere isso para 'top-center' para abrir para cima
                backdrop: 'dynamic',
                backdropClasses: 'bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-40',
                closable: true,
                onHide: () => {
                },
                onShow: () => {

                },
                onToggle: () => {

                },
            });
    },
    methods: {
                getSetting: function () {
            const _this = this;
            const settingStore = useSettingStore();
            const settingData = settingStore.setting;

            if (settingData) {
                _this.setting = settingData;
            }
        },
            checkIfMobile() {
      this.isMobile = window.innerWidth <= 900;
    },
            toggleBonus() {
      this.bonus = !this.bonus;
      this.wallet2 = !this.wallet2;
      this.historico = !this.historico;

    },
                    toggleModalDeposit: function() {
                this.modalDeposit.toggle();
            },
                                toggleModalSaque: function() {
                this.modalSaque.toggle();
            },
    getWallet: function() {
            const _this = this;
            const _toast = useToast();

            HttpApi.get('profile/wallet')
                .then(response => {
                    _this.wallet = response.data.wallet;
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                });
        },
        getWithdraws: function() {
            const _this = this;
            _this.isLoading = true;

            HttpApi.get('wallet/withdraw')
                .then(response => {
                    _this.withdraws = response.data.withdraws;
                    _this.isLoading = false;
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        console.log(`${value}`);
                    });
                    _this.isLoading = false;
                });
        },
        getDeposits: function() {
            const _this = this;
            _this.isLoading = true;

            HttpApi.get('wallet/deposit')
                .then(response => {
                    _this.deposits = response.data.deposits;
                    _this.isLoading = false;
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        console.log(`${value}`);
                    });
                    _this.isLoading = false;
                });
        },
    toggleHistorico() {
      this.historico = !this.historico;
    },
    
    setHistoricoDeposito() {
      this.dep = true;
      this.historico = true;

    },
    setHistoricoSaque() {
      this.saq = true;
            this.historico = true;

    },
            setDepositarTrue() {
      this.depositar = true;
    },
            setDepositarFalse() {
      this.depositar = false;
    },
    togglePerfil() {
      if (this.historico) {
        this.historico = false;
      this.saq = false;
      this.dep = false;

      } else {
        this.profile = !this.profile;
        this.wallet2 = !this.wallet2;
        
      }
    },
        
            getInitials: function(profilename) {
      if (!profilename) return '';
      const initials = profilename.split(' ').map(word => word[0]).join('');
      return initials;
    },
        
    toggleDropdown() {
      this.dropdownOpen = !this.dropdownOpen;
    },


                    closeNewElement() {
      // Lógica para fechar o novo elemento
      this.showNewElement = false;
      
    },
        toggleSearch: function() {
            this.searchGameDataStore.setSearchGameToogle();
        },
        redirectSocialTo: function() {
            return '/auth/redirect/google'
        },
        like: async function(id) {
            const _this = this;
            const _toast = useToast();
            await HttpApi.post('/profile/like/' + id, {})
                .then(response => {

                    _this.getProfile();
                    _toast.success(_this.$t(response.data.message));
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                });
        },
        updateName: async function(event) {
            const _this = this;
            _this.isLoadingProfile = true;

            await HttpApi.post('/profile/updateName', { name: _this.profileName })
                .then(response => {
                    _this.isLoadingProfile = false;
                })
                .catch(error => {
                    const _this = this;
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {

                    });
                    _this.isLoadingProfile = false;
                });
        },
        togglePassword: function() {
            if(this.typeInputPassword === 'password') {
                this.typeInputPassword = 'text';
            }else{
                this.typeInputPassword = 'password';
            }
        },
        loginSubmit: function(event) {
            const _this = this;
            const _toast = useToast();
            _this.isLoadingLogin = true;
            const authStore = useAuthStore();

            HttpApi.post('auth/login', _this.loginForm)
                .then(async response => {
                    await new Promise(r => {
                        setTimeout(() => {
                            authStore.setToken(response.data.access_token);
                            authStore.setUser(response.data.user);
                            authStore.setIsAuth(true);

                            _this.loginForm = {
                                email: '',
                                password: '',
                            }

                            _this.modalAuth.toggle();
                            _toast.success(_this.$t('You have been authenticated, welcome!'));

                            _this.isLoadingLogin = false;

                        }, 1000)
                    });

                })

                .catch(error => {
                    const _this = this;
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoadingLogin = false;
                });

        },
        registerSubmit: async function(event) {
            const _this = this;
            const _toast = useToast();
            _this.isLoadingRegister = true;

            const authStore = useAuthStore();
            await HttpApi.post('auth/register', _this.registerForm)
                .then(response => {
                    if(response.data.access_token !== undefined) {
                        authStore.setToken(response.data.access_token);
                        authStore.setUser(response.data.user);
                        authStore.setIsAuth(true);

                        _this.registerForm = {
                            name: '',
                            email: '',
                            password: '',
                            password_confirmation: '',
                            reference_code: '',
                            term_a: false,
                            agreement: false,
                        }

                        _this.modalRegister.toggle();
                        _this.router.push({ name: 'home' });
                        _toast.success(_this.$t('Cadastro Criado Com Sucesso!, aguarde!'));
                    }

                    _this.isLoadingRegister = false;
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoadingRegister = false;
                });
        },
        logoutAccount: function() {
            const authStore = useAuthStore();
            const _toast = useToast();

            HttpApi.post('auth/logout', {})
                .then(response => {
                    authStore.logout();
                    this.router.push({ name: 'home' });

                    _toast.success(this.$t('You have been successfully disconnected'));
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        console.log(value);
                        //_toast.error(`${value}`);
                    });
                });
        },
        hideLoginShowRegisterToggle: function() {
            this.modalAuth.toggle();
            this.modalRegister.toggle();
        },
        toggleMenu: function() {
            this.sidebarMenuStore.setSidebarToogle();
        },
        loginToggle: function() {
            this.modalAuth.toggle();
        },
        registerToggle: function() {
            this.modalRegister.toggle();
        },
        profileToggle: function() {
            this.modalProfile.toggle();
        },
        openFileInput() {
            this.$refs.fileInput.click();
        },
        async handleFileChange(event) {
            const file = event.target.files[0];
            const formData = new FormData();
            formData.append('avatar', file);

            const reader = new FileReader();
            reader.onload = () => {
                this.avatarUrl = reader.result;
            };
            reader.readAsDataURL(file);

            await HttpApi.post('/profile/upload-avatar', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            }).then(response => {
                console.log('Avatar atualizado com sucesso', response.data);
            })
                .catch(error => {
                    console.error('Erro ao atualizar avatar', error);
                });
        },
        getProfile: async function() {
            const _this = this;
            _this.isLoadingProfile = true;

            await HttpApi.get('/profile/')
                .then(response => {
                    _this.sumBets = response.data.sumBets;
                    _this.totalBets = response.data.totalBets;
                    _this.totalEarnings = response.data.totalEarnings;

                    const user = response.data.user;

                    if(user?.avatar != null) {
                        _this.avatarUrl = '/storage/'+user.avatar;
                    }

                    _this.profileName = user.name;
                    _this.profileUser = user;
                    _this.isLoadingProfile = false;
                })
                .catch(error => {
                    const _this = this;
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {

                    });
                    _this.isLoadingProfile = false;
                });
        },
        getSearch: async function() {
            const _this = this;

            await HttpApi.get('/search/games?searchTerm='+this.searchTerm)
                .then(response => {
                    _this.games = response.data.games;
                    _this.isLoadingSearch = false;
                })
                .catch(error => {
                    const _this = this;
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {

                    });
                    _this.isLoadingSearch = false;
                });
        },
        clearData: async function() {
            this.searchTerm = '';
            await this.getSearch();
        },
        

  
    },
    async created() {
        if(this.isAuthenticated) {

            await this.getProfile();
                    this.getWallet();
        this.getWithdraws();
        this.getDeposits();

        }
        this.getSetting();
        this.custom = custom;
    },
    watch: {
           walletInfo(newVal) {
      if (newVal > 0) {
        this.showBadge = !this.showBadge;
      }
    },
        searchTerm(newValue, oldValue) {
            this.getSearch();
        },
        async searchGameMenu(newValue, oldValue) {

            await this.getSearch();
            this.showSearchMenu = !this.showSearchMenu;
        },
    },
};
</script>

<style scoped>
.bonus-icon {
    display: block;
    height: 170px; /* Reduzido em 50% */
    width: auto;
    margin: 0 auto;
         margin-bottom: -20px; /* Ajuste conforme necessário para encostar no logo */
    top: -80px;
}



.rotate-90 {
    transform: rotate(90deg);
}


.text-red {
      color: #ff0000; /* Cor de fundo vermelha */

}



.with-navtop {
  margin-top: 40px; /* Define a margem superior quando o navtop estiver visível */
}
.translucent-menu {
    background: rgba(50, 50, 50, 0.9); /* Cinza translúcido mais opaco */
    backdrop-filter: blur(10px); /* Efeito embassado */
    -webkit-backdrop-filter: blur(10px); /* Suporte para Safari */
    border-top: 1px solid rgba(0, 0, 0, 0.1); /* Borda superior sutil */
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1); /* Sombra sutil */
}

.status-indicator {
    position: absolute;
    top: 5px; /* Ajuste a posição vertical */
    right: 10px; /* Ajuste a posição horizontal */
    width: 8px; /* Ajuste o tamanho */
    height: 8px; /* Ajuste o tamanho */
    border-radius: 50%;
    background-color: #01ec01; /* Cor verde */
    animation: blink 1s infinite alternate; /* Animação de piscar */
}

/* Animação de piscar */
@keyframes blink {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Garantir que não haja conflitos de estilos */
.fixed {
    background: rgba(0, 0, 0, 0.8) !important; /* Azul escuro translúcido */

}

.logo {
    display: block;
    height: 130px; /* Reduzido em 50% */
    width: auto;
    margin: 0 auto;
         margin-bottom: -20px; /* Ajuste conforme necessário para encostar no logo */

}

.input-group {
   background-color: #424344; /* Cor de fundo mais clara */
    color: #f2f2f2; /* Cor do texto mais escura */
    padding: 12px 16px; /* Aumenta o padding para deixar o input mais gordinho */
    padding-left: 40px; /* Adiciona padding à esquerda para os ícones */
    border-radius: 8px; /* Deixa as bordas mais arredondadas */
    font-size: 20px; /* Aumenta o tamanho da fonte */
    height: 60px; /* Define uma altura mínima para o input */
}
</style>
