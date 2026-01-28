'use client';

import Link from 'next/link';
import {
  Home,
  Wand2,
  BarChart3,
  History,
  Zap,
  CheckCircle,
  ArrowRight,
} from 'lucide-react';
import Header from '@/components/Header';

export default function LandingPage() {
  return (
    <div className="min-h-screen">
      <Header />

      {/* Hero Section */}
      <section className="relative overflow-hidden bg-gradient-to-br from-primary-600 to-primary-800 text-white">
        <div className="absolute inset-0 bg-black/10" />
        <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
          <div className="text-center">
            <h1 className="text-4xl md:text-6xl font-bold mb-6">
              Transformez vos annonces
              <br />
              <span className="text-primary-200">en aimants à visites</span>
            </h1>
            <p className="text-xl md:text-2xl text-primary-100 mb-8 max-w-3xl mx-auto">
              Notre IA réécrit vos annonces immobilières en textes professionnels
              qui donnent envie de visiter. Score qualité inclus.
            </p>
            <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
              <Link
                href="/register"
                className="btn bg-white text-primary-700 hover:bg-primary-50 px-8 py-3 text-lg flex items-center space-x-2"
              >
                <span>Commencer gratuitement</span>
                <ArrowRight className="w-5 h-5" />
              </Link>
              <Link
                href="/login"
                className="btn bg-primary-500/30 text-white hover:bg-primary-500/50 px-8 py-3 text-lg"
              >
                Se connecter
              </Link>
            </div>
            <p className="mt-4 text-primary-200 text-sm">
              3 annonces gratuites par mois, sans carte bancaire
            </p>
          </div>
        </div>

        {/* Wave decoration */}
        <div className="absolute bottom-0 left-0 right-0">
          <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
              d="M0 120L60 110C120 100 240 80 360 70C480 60 600 60 720 65C840 70 960 80 1080 85C1200 90 1320 90 1380 90L1440 90V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z"
              fill="rgb(245, 247, 250)"
            />
          </svg>
        </div>
      </section>

      {/* Features */}
      <section className="py-20 bg-gradient-to-b from-gray-50 to-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Comment ça fonctionne ?
            </h2>
            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
              En 3 étapes simples, transformez vos annonces brutes en textes
              professionnels.
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            <div className="card text-center">
              <div className="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <Home className="w-8 h-8 text-primary-600" />
              </div>
              <h3 className="text-xl font-semibold text-gray-900 mb-3">
                1. Collez votre annonce
              </h3>
              <p className="text-gray-600">
                Copiez-collez le texte brut de votre annonce immobilière, aussi
                basique soit-il.
              </p>
            </div>

            <div className="card text-center">
              <div className="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <Wand2 className="w-8 h-8 text-primary-600" />
              </div>
              <h3 className="text-xl font-semibold text-gray-900 mb-3">
                2. Choisissez le style
              </h3>
              <p className="text-gray-600">
                Sélectionnez le type de bien, la transaction et la gamme. Notre IA
                adapte le ton.
              </p>
            </div>

            <div className="card text-center">
              <div className="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <BarChart3 className="w-8 h-8 text-primary-600" />
              </div>
              <h3 className="text-xl font-semibold text-gray-900 mb-3">
                3. Obtenez 3 versions
              </h3>
              <p className="text-gray-600">
                Recevez une version pro, courte et premium avec score qualité.
                Copiez et utilisez.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Benefits */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-2 gap-12 items-center">
            <div>
              <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
                Des annonces qui font la différence
              </h2>
              <div className="space-y-4">
                {[
                  'Textes professionnels sans fautes',
                  'Phrases claires et percutantes',
                  'Vocabulaire immobilier expert',
                  'Score qualité pour valider',
                  'Historique de vos annonces',
                  '3 versions par génération',
                ].map((benefit, index) => (
                  <div key={index} className="flex items-center space-x-3">
                    <CheckCircle className="w-6 h-6 text-green-500 flex-shrink-0" />
                    <span className="text-gray-700">{benefit}</span>
                  </div>
                ))}
              </div>
            </div>
            <div className="bg-gray-50 rounded-2xl p-8">
              <div className="space-y-4">
                <div className="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                  <p className="text-sm font-medium text-red-800 mb-1">Avant</p>
                  <p className="text-gray-600 text-sm">
                    "Appart 3p 65m2 metro balcon refait cave"
                  </p>
                </div>
                <div className="flex justify-center">
                  <ArrowRight className="w-8 h-8 text-primary-500" />
                </div>
                <div className="bg-green-50 border-l-4 border-green-400 p-4 rounded">
                  <p className="text-sm font-medium text-green-800 mb-1">Après</p>
                  <p className="text-gray-600 text-sm">
                    "Lumineux appartement de 3 pièces (65m²) entièrement rénové,
                    situé à deux pas du métro. Profitez d'un balcon ensoleillé et
                    d'une cave privative. L'espace de vie fonctionnel offre un
                    cadre idéal..."
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Pricing */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Tarifs simples et transparents
            </h2>
            <p className="text-xl text-gray-600">
              Commencez gratuitement, passez Pro quand vous voulez.
            </p>
          </div>

          <div className="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            {/* Free Plan */}
            <div className="card border-2 border-gray-200">
              <h3 className="text-2xl font-bold text-gray-900 mb-2">Gratuit</h3>
              <p className="text-gray-600 mb-6">Pour découvrir le service</p>
              <div className="text-4xl font-bold text-gray-900 mb-6">
                0€<span className="text-lg font-normal text-gray-500">/mois</span>
              </div>
              <ul className="space-y-3 mb-8">
                {[
                  '3 annonces par mois',
                  '3 versions générées',
                  'Score qualité',
                  'Historique 30 jours',
                ].map((feature, index) => (
                  <li key={index} className="flex items-center space-x-2">
                    <CheckCircle className="w-5 h-5 text-green-500" />
                    <span className="text-gray-600">{feature}</span>
                  </li>
                ))}
              </ul>
              <Link href="/register" className="btn btn-secondary w-full text-center">
                Commencer gratuitement
              </Link>
            </div>

            {/* Pro Plan */}
            <div className="card border-2 border-primary-500 relative">
              <div className="absolute -top-4 left-1/2 transform -translate-x-1/2">
                <span className="bg-primary-500 text-white px-4 py-1 rounded-full text-sm font-medium">
                  Populaire
                </span>
              </div>
              <h3 className="text-2xl font-bold text-gray-900 mb-2">Pro</h3>
              <p className="text-gray-600 mb-6">Pour les professionnels</p>
              <div className="text-4xl font-bold text-gray-900 mb-6">
                19€<span className="text-lg font-normal text-gray-500">/mois</span>
              </div>
              <ul className="space-y-3 mb-8">
                {[
                  'Annonces illimitées',
                  '3 versions générées',
                  'Score qualité détaillé',
                  'Historique illimité',
                  'Support prioritaire',
                ].map((feature, index) => (
                  <li key={index} className="flex items-center space-x-2">
                    <CheckCircle className="w-5 h-5 text-primary-500" />
                    <span className="text-gray-600">{feature}</span>
                  </li>
                ))}
              </ul>
              <Link href="/register" className="btn btn-primary w-full text-center">
                Passer au Pro
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-20 bg-primary-600">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-3xl md:text-4xl font-bold text-white mb-6">
            Prêt à booster vos annonces ?
          </h2>
          <p className="text-xl text-primary-100 mb-8">
            Rejoignez les agents immobiliers qui génèrent plus de visites avec des
            annonces professionnelles.
          </p>
          <Link
            href="/register"
            className="btn bg-white text-primary-700 hover:bg-primary-50 px-8 py-3 text-lg inline-flex items-center space-x-2"
          >
            <Zap className="w-5 h-5" />
            <span>Créer mon compte gratuit</span>
          </Link>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-gray-400 py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex flex-col md:flex-row justify-between items-center">
            <div className="flex items-center space-x-2 mb-4 md:mb-0">
              <div className="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                <Home className="w-5 h-5 text-white" />
              </div>
              <span className="text-xl font-bold text-white">RedacImmo</span>
            </div>
            <div className="flex space-x-6">
              <Link href="/mentions-legales" className="hover:text-white transition-colors">
                Mentions légales
              </Link>
              <Link href="/cgu" className="hover:text-white transition-colors">
                CGU
              </Link>
              <Link href="/contact" className="hover:text-white transition-colors">
                Contact
              </Link>
            </div>
          </div>
          <div className="mt-8 pt-8 border-t border-gray-800 text-center text-sm">
            © {new Date().getFullYear()} RedacImmo. Tous droits réservés.
          </div>
        </div>
      </footer>
    </div>
  );
}
