=== WooCommerce Payment Gateway - tpay.com  ===
Contributors: tpay.com
Donate link: https://tpay.com/
Tags: woocommerce, tpay, payment, polish gateway, polska brama płatności, bramka płatności, płatności internetowe
Requires at least: 2.0.0
Tested up to: 4.9.8
Stable tag: 2.6.51
License: GPLv2
Accept payments from all major polish banks directly on your WooCommerce site via tpay.com polish payment gateway system.

== Description ==

Brama płatności dla pluginu Woocommerce.

tpay.com to system szybkich płatności online należący do spółki Krajowy Integrator Płatności SA. Misją przedsiębiorstwa jest wprowadzanie oraz propagowanie nowatorskich metod płatności i rozwiązań płatniczych zapewniających maksymalną szybkość i bezpieczeństwo dokonywanych transakcji.

Jako lider technologiczny, tpay.com oferują największą liczbę metod płatności na rynku. W ofercie ponad 50 sposobów zapłaty znajdą Państwo m.in. największy wybór e-transferów, Zintegrowaną Bramkę Płatności Kartami, mobilną galerię handlową RockPay oraz narzędzie do zbiórek pieniężnych w sieci – serwis eHat.me. Dodatkowe funkcjonalności systemu obejmują pełen design w RWD, przelewy masowe oraz udostępnione biblioteki mobilne i dodatki do przeglądarek automatyzujące przelewy. tpay.com oferuje również płatności odroczone, raty online Premium SMS oraz płatność za pomocą kodu QR.

tpay.com zapewnia najwyższy poziom bezpieczeństwa potwierdzony certyfikatem PCI DSS Level 1. System gwarantuje wygodę oraz możliwość natychmiastowej realizacji zamówienia. Oferta handlowa tpay.com jest dokładnie dopasowana do Twoich potrzeb.

tpay.com Online Payment System belongs to Krajowy Integrator Płatności Inc. The company’s mission is to introduce and promote innovative payment methods and solutions ensuring maximum speed and safety of online transactions.

As technological leader, tpay.com offers the largest number of payment methods on market. Among over 50 ways of finalizing transactions you will find the widest choice of direct online payments, Integrated Card Payment Gate, mobile shopping center – RockPay and group payments tool – eHat.me. Additional features include: RWD design, mass pay-outs, mobile libraries and payment automation application. You can also pay using postponed payment, online installments, Premium SMS and QR code payment.

The highest level of security of payments processed by tpay.com is verified by PCI DSS Level 1 certificate. System guarantees convenience and instant order execution. Our business offer is flexible and prepared according to your needs.


== Installation ==

= WYMAGANIA =

Aby korzystać z płatności tpay.com w platformie Woocommerce niezbędne jest:

a)	Posiadanie konta w systemie tpay.com
b)	Aktywna wtyczka WooCommerce dla Wordpressa.
c)	Pobranie plików instalacyjnych modułu z katalogu wtyczek Wordpress:



= INSTALACJA MODUŁU =

Instalacja autmatyczna
a)	Przejdź do menu „Wtyczki” następnie „Dodaj nową” i w miejscu „Szukaj wtyczek”  wyszukaj „tpay”
b)	W „Wynikach wyszukiwania” pojawi się moduł płatności tpay, który należy zainstalować.


Instalacja ręczna
a)	Rozpakuj zawartość archiwum na dysk. Po rozpakowaniu powinien powstać folder „woocommerce_transferuj”.
b)	Wyślij cały folder  do katalogu wp-content/plugins znajdującego się w Twojej instalacji Wordpress.

1.	Przejdź do panelu administracyjnego i otwórz zakładkę „Wtyczki”. Kliknij „Włącz” przy pozycji „tpay.pl”.
2.	Przejdź do WooCommerce ->Ustawienia i wybierz  zakładkę „Zamówienia ” po czym z listy dostępnych metod płatności  wybierz tpay.com.
3.	Teraz należy dokonać odpowiednich ustawień dla modułu płatności tpay:
	a.	Włącz/Wyłącz – należy pozostawić zaznaczone, aby klienci mogli dokonywać płatności przez tpay.
	b.	Nazwa – nazwa płatności
	c.	Opis - opis bramki płatności tpay, który widzi użytkownik przy tworzeniu zamówienia
	d.	ID sprzedawcy – pole obowiązkowe, Twój ID otrzymany podczas zakładania konta  tpay.com
	e.	Kod bezpieczeństwa  – należy wpisać kod  ustawiony w Panelu Odbiorcy Płatności  w tpay.com. Menu -> Ustawienia -> Powiadomienia -> Kod 		Bezpieczeństwa.
	f.	Dopłata doliczana za korzystanie z tpay – opcja ta pozwala doliczyć do kwoty zamówienia, opłatę  za korzystanie płatności tpay. Domyślnie 		wybrana jest opcja NIE pozostałe opcje:
			PLN – należy podać kwotę jaka ma zostać doliczona do zamówienia
			% - należy podać jaki procent z danego zamówienia zostanie doliczony do całkowitej kwoty do zapłaty.
	g.	Kwota dopłaty – dla wybranej w poprzednim punkcie opcji:
			PLN- kwota doliczana do zamówienia, liczby dziesiętne należy podać po kropce np.  3.50
			% - procent jaki ma zostać doliczony z danego zamówienia do całkowitej kwoty zamówienia, liczby dziesiętne należy podać po kropce np. 2.75
	h.	Włącz wybór banku na stronie sklepu– dostępne opcje:
			TAK – klient będzie dokonywał wyboru kanału płatności na stronie sklepu.
			NIE – klient dokona wyboru kanału płatności po przejściu do Panelu Transakcyjnego tpay.
	i.	Widok listy kanałów- pozwala wybrać na jakiej zasadzie mają być wyświetlane kanały płatności na stronie sprzedawcy:
			Lista – rozwijana lista zawierająca kanały płatności.
			Kafelki – kanały płatności wyświetlane w formie ikon z logami banków.
		Opcja brana pod uwagę tylko z aktywną opcją h.
4.	Następnie należy kliknąć „Zapisz zmiany”.


= Testy =

Moduł był testowany na systemie zbudowanym z wersji Woocommerce 3.4.4 i Wordpress 4.9.8.


= KONTAKT =

W razie potrzeby odpowiedzi na pytania powstałe podczas lektury lub szczegółowe wyjaśnienie kwestii technicznych prosimy o kontakt poprzez formularz znajdujący się w Panelu Odbiorcy lub na adres e-mail: pt@tpay.com

== Changelog ==
v2.6.51
Usunięto zdublowane wyświetlanie informacji o prowizji za płatność
v2.6.50
Wyświetlanie bramki płatności kartami w języku polskim i angielskim
v2.6.47
Poprawiono kompatybilność z edytorem menu
v2.6.46
Naprawiono konflikt z przestarzałą metodą wysyłki local_pickup
v2.6.45
Poprawki kodu źródłowego
v2.6.44
Dodano zabezpieczenie przed błędami pobierania listy dostępnych metod wysyłki
v2.6.43
Dodano logowanie błędów BLIK
Dodano automatycznie przekierowanie do panelu transakcyjnego w przypadku błędnego kodu blik
Dodano zabezpieczenie przed błędami pobierania listy dostępnych metod wysyłki
v2.6.42
Zabezpieczenie przed wyjątkami w pobieraniu metod wysyłki
v2.6.41
Zabezpieczenie przed wyjątkami w pobieraniu metod wysyłki
Rozpoznawanie języka w opisie transakcji
v2.6.4
Aktualizacja kompatybilności ze starszymi wersjami Woocommerce
v2.6.3
Dodano parowanie metody płatności z metodą wysyłki.
Zaktualizowano bibliotekę szyfrującą dane karty.
v2.6.2
Poprawiony błędny link w formularzu płatności kartą.
v.2.6.1
Poprawiono błąd zwracany przy odrzuconej płatności kartowej bez 3DS.
v2.6.0
Poprawiono przekierowania na uszkodzone adresy URL stron sukcesu i niepowodzenia.
v2.5.9
Zmodyfikowano wyświetlanie kanału Raty
Usunięto nadpisanie CSS zaznaczenia tekstu
v2.5.8
Zmodyfikowano zarządzanie stanem magazynowym. Zmieniono stronę błędu na adres ponawiania zapłaty.
v2.5.7
Wyłączono wyświetlanie regulaminu w trybie przekierowania do panelu transakcyjnego.
v2.5.6
Rozwiązano problem z autokorektą wprowadzanego numeru karty kredytowej
v2.5.5
Dodano możliwość wyłączenia walidacji IP serwera powiadomień
v2.5.4
Rozwiązano konflikt z PHP 7.1
v2.5.3
Dodano zbieranie statystyk modułu
v2.5.2
Poprawiono nadpisywanie wyglądu pól formularza płatności.
v2.5.1
Dodano opcję automatycznego oznaczania zamówienia jako zrealizowane
v2.5.0
Dodano obsługę zwrotów z panelu widoku zamówienia. Aby opcja zwrotów była aktywna i działała poprawnie, należy wygenerować i wprowadzić klucz API w ustawienia wtyczki zgodnie z instrukcją https://secure.tpay.com/integration/instruction/64
v2.4.4
Poprawa wyświetlania polskich znaków
v2.4.3
Optymalizacja kodu i wyświetlania błędów
v2.4.2
Naprawiono błąd ładowania klasy pomocniczej Util
v2.4.1
Zmodyfikowano sprawdzanie adresów IP serwerów powiadomień tpay.
v2.4.0
Dodano weryfikację obsługiwanych typów kart płatniczych i blokowanie prób zapłaty nieobsługiwanymi kartami.
Usunięto zbędne pola z formularza płatności kartami.
v2.3.9
Dodane nowe adresy IP serwerów powiadomień dla transakcji kartowych
v2.3.8
Poprawka problemu z instalacją "niepoprawny nagłówek"
Poprawka ostrzeżeń w php
v2.3.7
Zmodyfikowano wygląd płatniści blik na stronie sklepu
v2.3.6
Dodano kompatybilność z nowymi metodami woocommerce
Poprawiono kompatybilność z PHP 5.3
v2.3.5
Poprawiono wyświetlania bramki kartowej
Poprawiono obsługę wpisania złych danych karty
v2.3.4
Po nieudanej płatności zalogowany użytkownik będzie przekierowany do strony swojego konta, gdzie będzie mógł ją ponowić.
Optymalizacja kodu źródłowego.
v2.3.3
Po złożeniu zamówienia status zostanie ustawiony na wbudowany w woocommerce - oczekuje na płatność.
Kupujący ma od teraz możliwość ponowienia płatności ze strony swojego konta.
v2.3.2
Poprawiono wyświetlanie formularza blik.
Poprawiono wykrywanie ssl.
Poprawiono odbieranie powiadomień bramki kartowej.
v2.3.1
Poprawiono kompatybilność z PHP 5.3
v2.3.0
Poprawiono wyświetlanie formularza blik level 0.
Zaktualizowano biblioteki php.
Dodano usuwanie niedozwolonych znaków z danych kupującego.
Usunięto wymaganie id sprzedawcy oraz kodu bezpieczeństwa dla wtyczki kartowej.
v2.2.9
Usunięto nadpisywanie źródła ruchu w Google Analytics.
Poprawiono walidację transakcji kartowych.
Poprawiono wygląd i funkcjonowanie formularza płatności kartami.
Dodano logowanie czynności w pliku log.
v2.2.8
Poprawiono rozpoznawanie adresu IP w odbieraniu powiadomień.
v2.2.7
Poprawiono automatyczne wysyłanie formularza płatności.
v2.2.6
Poprawiono walidację.
v2.2.5
Zmieniono reguły walidacji adresów URL oraz wyświetlanie regulaminu.
v2.2.4
Zmieniono reguły walidacji adresów URL
v2.2.3
Dodano przesyłanie adresów powrotnych dla transakcji kartowych z 3DS
v2.2.2
Poprawiono wyświetlanie w trybie widoku bez kanałów płatności
v2.2.1
Dodano tryb debugowania
v2.2.0
Dodano obsługę wielu kont dla płatności kartami
v2.1.2
Dodano znacznik # (hash) do wyjątków walidacji ze względu na konflikty z innymi wtyczkami
Poprawiono ładowanie javascript
v2.1.1
Poprawiono wyświetlanie bramki kartowej.
Poprawiono błąd ładowania javascript.
v2.1.0
Dodano metodę płatności przez zintegrowaną bramkę kartową w wielu walutach. Naprawiono błędy w walidacji danych.
v2.0.4
Poprawiono błędy javascript i link regulaminu
v2.0.3
Poprawiono przekierowanie po płatności blikiem
v2.0.2
Poprawiono błąd sprawdzania https sklepu
v2.0.1
Porawiono błąd dotyczący załączania plików stylu.
v2.0.0
Prosimy przed istalacją upewnić się, że serwer ma wersję PHP minimum 5.6
Reorganizacja kodu źródłowego
Implementacja bibliotek tpay.com
Dodano własny opis transakcji
Poprawa błędów wyświetlania
v1.3.3
Naprawiono błąd powodujący nie przesyłanie parametru akceptacji regulaminu.
v1.3.2
Naprawiono błąd powodujący wyświetlenie informacji o braku kanału płatności.
v1.3.1
Naprawiono błąd uniemożliwiający płatność innymi metodami.
Naprawiono błąd powodujący generowanie więcej niż jednej listy z kanałami płatności.
v1.3.0
Dodano płatność blik na stronie sklepu (level 0).
Udoskonalono wygląd wtyczki po stronie sklepu.
v1.2.2
Dodano adresy nowych serwerów powiadomień.
Prosimy wykonać aktualizację ze względu na nadchodzącą zmianę adresów serwerów powiadomień.

== Frequently Asked Questions ==
Feel free to contact us on info@tpay.com
== Upgrade Notice ==
= 2.5.0 =
Dodano obsługę zwrotów z panelu widoku zamówienia. Aby opcja zwrotów była aktywna i działała poprawnie, należy wygenerować i wprowadzić klucz API w ustawienia wtyczki zgodnie z instrukcją https://secure.tpay.com/integration/instruction/64
== Screenshots ==
no screenshots
