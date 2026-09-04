<x-mail::message>
    # Bienvenue sur votre plateforme !

    Votre espace d'entreprise **{{ $tenantName }}** a été créé avec succès.

    <x-mail::panel>
        **Vos identifiants d'accès administrateur :**
        * **URL d'accès :** [{{ $loginUrl }}]({{ $loginUrl }})
        * **Email :** {{ $email }}
        * **Mot de passe temporaire :** `{{ $password }}`
    </x-mail::panel>

    Pour des raisons de sécurité, nous vous invitons à modifier ce mot de passe dès votre première connexion.

    <x-mail::button :url="$loginUrl">
        Se connecter à mon espace
    </x-mail::button>

    Merci de votre confiance,<br>
    L'équipe {{ config('app.name') }}
</x-mail::message>
