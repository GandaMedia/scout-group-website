<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Filament\Blocks\ContactDetailsBlock;
use App\Filament\Blocks\ContactFormBlock;
use App\Filament\Blocks\CtaBlock;
use App\Filament\Blocks\GoogleMapBlock;
use App\Filament\Blocks\HeroBlock;
use App\Filament\Blocks\ImageTextBlock;
use App\Filament\Blocks\RichTextBlock;
use App\Filament\Blocks\SectionLeadersBlock;
use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $pageData) {
            $page = Page::query()->updateOrCreate(
                ['slug' => $pageData['slug']],
                [
                    'title' => $pageData['title'],
                    'status' => PageStatus::PUBLISHED,
                ],
            );

            $this->seedBlocksFor($page, $pageData['blocks']);
        }
    }

    /**
     * @param  list<array{block_type: class-string, data: array<string, mixed>}>  $blocks
     */
    private function seedBlocksFor(Page $page, array $blocks): void
    {
        $page->pageBuilderBlocks()->delete();

        foreach ($blocks as $index => $block) {
            $page->pageBuilderBlocks()->create([
                'block_type' => $block['block_type'],
                'order' => $index + 1,
                'data' => $block['data'],
            ]);
        }
    }

    /**
     * @return list<array{title: string, slug: string, blocks: list<array{block_type: class-string, data: array<string, mixed>}>}>
     */
    private function pages(): array
    {
        return [
            $this->page('Squirrels', 'squirrels', [
                $this->hero('Squirrels (Ages 4-6)', 'Big Adventures Begin with Tiny Steps', 'Every great adventure starts with a single step, and for many young people, that first step is Squirrels.', '/img/cubs-in-helmets-outdoors-jpg.jpg', 'Join the waiting list', '/join/squirrels'),
                $this->imageText('A warm welcome to their first adventure', "Our Squirrel Drey provides a fun, energetic and welcoming environment where children aged 4 to 6 can discover the excitement of Scouting, make new friends and begin building confidence through play and exploration.\n\nOur programme is packed with crafts, games, stories, simple cooking and practical activities while working towards badges and introducing the values that make Scouting special. Every meeting is designed to spark curiosity, encourage teamwork and lay the foundations for a lifelong love of adventure.", '/img/cubs-in-helmets-outdoors-jpg.jpg', 'right'),
                $this->richText("## When We Meet\n\nAdd the Squirrel meeting day, time and location in the page editor.\n\n## Interested in Joining?\n\nWe'd love to welcome your child to {{ group_name }}. Whether they're completely new to Scouting or joining from another group, they'll receive a warm welcome from our volunteers and young people."),
                $this->sectionLeaders('Squirrels'),
                $this->cta('Ready to join Squirrels?', 'Get in touch today to arrange a free taster session, ask any questions or add your child to our waiting list.', 'Join the waiting list', '/join/squirrels'),
            ]),
            $this->page('Beavers', 'beavers', [
                $this->hero('Beavers (Ages 6-8)', 'Where Confidence Grows and Adventure Begins', 'Beavers is where curiosity becomes confidence and every week brings a new adventure.', '/img/cubs-in-helmets-outdoors-jpg.jpg', 'Join the waiting list', '/join/beavers'),
                $this->imageText('Skills for life start here', "Building on everything they've learned in Squirrels, Beavers begin taking on bigger challenges while continuing to learn through fun, hands-on experiences. It's a time when young people really begin to discover what they're capable of.\n\nFrom exciting games and outdoor adventures to camps and practical activities, every meeting is designed to keep young people engaged while developing teamwork, resilience and independence. Along the way they build friendships, earn badges and create memories that last a lifetime.\n\nOur experienced leadership team provides a safe, supportive and inclusive environment where every Beaver is encouraged to step outside their comfort zone, try something new and celebrate every achievement, no matter how big or small.\n\nAdventure starts here, and the skills developed in Beavers prepare young people for everything that lies ahead.", '/img/cubs-in-helmets-outdoors-jpg.jpg'),
                $this->richText("## When We Meet\n\nAdd the Beaver meeting day, time and location in the page editor.\n\n## Interested in Joining?\n\nWe'd love to welcome your child to {{ group_name }}. Whether they're completely new to Scouting or joining from another group, they'll receive a warm welcome from our volunteers and young people."),
                $this->sectionLeaders('Beavers'),
                $this->cta('Ready to join Beavers?', 'Get in touch today to arrange a free taster session, ask any questions or add your child to our waiting list.', 'Join the waiting list', '/join/beavers'),
            ]),
            $this->page('Cubs', 'cubs', [
                $this->hero('Cubs (Ages 8-10½)', 'Adventure Awaits Around Every Corner', 'By the time young people reach Cubs, they\'re ready for bigger adventures, greater challenges and even more opportunities to discover what they\'re capable of.', '/img/cubs-in-helmets-outdoors-jpg.jpg', 'Join the waiting list', '/join/cubs'),
                $this->imageText('A bigger world opens up', "Our Cub Pack is curious, energetic and full of fun. Every meeting combines exciting activities with practical learning, helping young people grow in confidence while developing the independence they'll carry into Scouts and beyond.\n\nThroughout the year, Cubs can take part in camping, hiking, first aid, cooking, pioneering and outdoor skills alongside activity days and nights away. The best learning happens when young people are involved, challenged and encouraged to have a go.", '/img/cubs-in-helmets-outdoors-jpg.jpg', 'right'),
                $this->richText("## When We Meet\n\nAdd the Cub meeting day, time and location in the page editor.\n\n## Interested in Joining?\n\nWe'd love to welcome your child to {{ group_name }}. Whether they're completely new to Scouting or joining from another group, they'll receive a warm welcome from our volunteers and young people."),
                $this->sectionLeaders('Cubs'),
                $this->cta('Ready to join Cubs?', 'Get in touch today to arrange a free taster session, ask any questions or add your child to our waiting list.', 'Join the waiting list', '/join/cubs'),
            ]),
            $this->page('Scouts', 'scouts', [
                $this->hero('Scouts (Ages 10½-14)', "Preparing Young People for Life's Greatest Adventures", 'Scouting is about far more than camping and badges. It\'s about preparing young people for life, giving them the confidence, resilience and practical skills they\'ll carry with them long after they leave our Troop.', '/img/cubs-in-helmets-outdoors-jpg.jpg', 'Join the waiting list', '/join/scouts'),
                $this->imageText('Challenge, growth and community', "Our Scout section is engaging, fun and supportive, with one clear aim: to bring out the best in every young person. We encourage Scouts to challenge themselves, step outside their comfort zone and discover what they're capable of.\n\nA varied programme can include camping, hiking, navigation, pioneering, cooking, fundraising and teamwork, balanced with games and challenges that make every meeting something to look forward to. Scouts work towards badges and awards while building a community based on kindness, respect and mutual support.", '/img/cubs-in-helmets-outdoors-jpg.jpg'),
                $this->richText("If you're looking for an activity that's exciting, inclusive and focused on developing skills for life, {{ group_name }} is ready to welcome your family.\n\n## When We Meet\n\nAdd the Scout meeting day, time and location in the page editor.\n\n## Interested in Joining?\n\nWe'd love to welcome your child to {{ group_name }}. Whether they're completely new to Scouting or joining from another group, they'll receive a warm welcome from our volunteers and young people."),
                $this->sectionLeaders('Scouts'),
                $this->cta('Ready to join Scouts?', 'Get in touch today to arrange a free taster session, ask any questions or add your child to our waiting list.', 'Join the waiting list', '/join/scouts'),
            ]),
            $this->page('Explorers', 'explorers', [
                $this->hero('Explorers (Ages 14-18)', 'Take the Lead on Bigger Adventures', 'Explorers is where young people step into more independence, shape their own programme and take on adventures that stretch what they think is possible.', '/img/cubs-in-helmets-outdoors-jpg.jpg', 'Join the waiting list', '/join/explorers'),
                $this->imageText('A programme shaped by young people', "Explorer Scouts meet in Units and have much more freedom to choose what they do. Working with adult volunteers, young people can build a programme around real-life skills, wellbeing, community impact and the challenges that matter to them.\n\nThat could mean an expedition, learning to code, planning a camp, taking on an environmental project or mastering a practical skill. No two weeks need to look the same, and Units can be flexible around exams, study and other commitments.\n\nExplorers can also develop leadership through the Young Leaders' Scheme by volunteering alongside Squirrels, Beavers, Cubs or Scouts. It is a supported way to plan activities, lead games and help younger members gain skills for life.", '/img/cubs-in-helmets-outdoors-jpg.jpg', 'right'),
                $this->richText("## Awards and bigger goals\n\nExplorer Scouts can work towards the Chief Scout's Platinum and Diamond Awards, the King's Scout Award, the Duke of Edinburgh's Award, the Explorer Belt and the Young Leader Award. These challenges recognise sustained effort, teamwork, resilience, service and adventure.\n\n## Finding an Explorer Unit\n\nExplorer Units are supported through the local Scout District and may have close links with Scout Groups. Meeting patterns vary, so register your interest and we will connect you with the current opportunity in your area.\n\nRead more about the current [Explorer programme and awards on Scouts UK](https://www.scouts.org.uk/explorers/)."),
                $this->sectionLeaders('Explorers'),
                $this->cta('Ready to explore what comes next?', 'Register your interest and we will help you find the right local Explorer Unit or Young Leader opportunity.', 'Register your interest', '/join/explorers'),
            ]),
            $this->page('Network', 'network', [
                $this->hero('Network (Ages 18-25)', 'Keep Adventure Part of Adult Life', 'Scout Network is for 18 to 25 year olds who want to keep building skills, friendships and adventures around adult life.', '/img/cubs-in-helmets-outdoors-jpg.jpg', 'Join the waiting list', '/join/network'),
                $this->imageText('Projects, events and self-led adventure', "Scout Network is flexible and shaped by its members. Young adults organise projects, events and activities around three broad themes: Adventure, Community and International. It can fit alongside work, study, travel and an adult volunteer role.\n\nMembers might arrange a local meet-up, support a community project, plan an international experience or form a project team around a shared goal. Along the way they practise leadership, teamwork, communication, budgeting and project management.\n\nNetwork is open to people who are completely new to Scouts as well as those moving on from Explorers. Members manage their own journey with support from the District 14-24 Team.", '/img/cubs-in-helmets-outdoors-jpg.jpg'),
                $this->richText("## Awards and opportunities\n\nNetwork members can work towards the Chief Scout's Diamond Award, King's Scout Award, Duke of Edinburgh's Award, Explorer Belt and Scouts of the World Award. The Scouts of the World Award is exclusive to Network and supports a substantial project connected to peace, the environment or sustainability.\n\n## Finding Scout Network locally\n\nScout Network is District-based and does not need to follow a weekly section-night pattern. Register your interest and we will connect you with the District Network team and the current projects, events and opportunities available to 18 to 25 year olds.\n\nExplore the [Scout Network programme on Scouts UK](https://www.scouts.org.uk/network)."),
                $this->sectionLeaders('Network'),
                $this->cta('Ready for your next project or adventure?', 'Register your interest and we will connect you with the District Scout Network.', 'Register your interest', '/join/network'),
            ]),
            $this->page('Contact', 'contact', [
                $this->hero('Talk to the leaders', 'Start your Scouting journey', 'Whether your family is ready for Squirrels, Beavers, Cubs, Scouts, Explorers or Network, we would love to help you find the right section and make the first step feel easy.', '/img/cubs-in-helmets-outdoors-jpg.jpg', 'Browse our sections', '/'),
                $this->contactDetails(
                    'Contact the group',
                    'The quickest way to get started is to come along to the section night that matches your child\'s age, introduce yourself to the leaders and tell us a little about what you are looking for. If a section has a waiting list, we will talk you through the next steps straight away.',
                    [
                        [
                            'title' => 'Where to find us',
                            'body' => "Add the headquarters name and address in Admin > Group profile.",
                        ],
                        [
                            'title' => 'What to tell us',
                            'body' => "Your child's first name\nTheir age or school year\nWhich section you are interested in\nAny questions or support needs you would like us to know about",
                        ],
                        [
                            'title' => 'Section nights',
                            'body' => "Add current section meeting days and times in the page editor. Explorer and Network opportunities are usually coordinated through the local District.",
                        ],
                        [
                            'title' => 'What happens next',
                            'body' => 'We will introduce you to the leadership team, answer any questions, arrange a taster session and let you know straight away if there is a waiting list for your chosen section.',
                        ],
                    ],
                    'See our section pages',
                    '/',
                    'View Scouts',
                    '/scouts',
                ),
                $this->contactForm('Send us a message', 'Use the short form below if you would rather contact us online. We only need a few details to point your message to the right leaders and get back to you quickly.'),
                $this->googleMap('Find our HQ', 'The map and address below are managed in the admin area, so the venue details can be updated without editing this page.'),
                $this->richText("## Joining {{ group_name }}\n\nWe want joining Scouts to feel straightforward and welcoming. If your child is brand new to Scouting, that is absolutely fine. We will help you work out the right section and explain what to expect before their first meeting.\n\n## A good first conversation usually covers\n\n- your child's age and current school year\n- which evenings are easiest for your family\n- any medical, behavioural or accessibility information we should know\n- whether you are looking for an immediate place or to join a waiting list\n\n## Visiting for the first time\n\nYoung people usually settle in best when they know what is coming, so we are always happy to talk through meeting times, uniform, activities and what happens on arrival. Our aim is simple: a warm welcome, clear communication and a great first experience."),
                $this->cta('Ready to find the right section?', 'Take a look at our section pages, compare meeting nights and choose the adventure that fits your child best.', 'Explore sections', '/'),
            ]),
            $this->page('Data protection policy', 'privacy', [
                $this->hero('Privacy and GDPR', 'Data protection policy', 'How the group collects, uses, protects and shares personal information.', '/img/cubs-in-helmets-outdoors-jpg.jpg'),
                $this->richText($this->dataProtectionContent()),
            ]),
            $this->page('Cookie policy', 'cookie-policy', [
                $this->hero('Privacy and cookies', 'Cookie policy', 'The cookies and similar storage used to keep this website secure and working properly.', '/img/cubs-in-helmets-outdoors-jpg.jpg'),
                $this->richText($this->cookiePolicyContent()),
            ]),
            $this->page('Website terms and conditions', 'terms', [
                $this->hero('Website terms', 'Using this Scout Group website', 'These terms set expectations for public visitors, registered leaders and administrators using this service.', '/img/cubs-in-helmets-outdoors-jpg.jpg'),
                $this->richText($this->termsContent()),
            ]),
        ];
    }

    private function dataProtectionContent(): string
    {
        return <<<'MARKDOWN'
## Who is responsible for your information

{{ group_name }} is an independent data controller for the personal information it collects and uses for local Scouting. The Group Trustee Board is responsible for making sure that information is handled lawfully, fairly and securely. The Scout Association is a separate data controller for information it processes through national services.

Questions about this policy or requests concerning your information should be sent through the contact page.

## Our data-protection principles

We follow the seven UK GDPR principles. Personal information must be used lawfully, fairly and transparently; collected for clear purposes; limited to what is necessary; kept accurate; retained only as long as needed; kept secure; and handled in a way for which the group can demonstrate accountability.

## Information we may collect

Depending on your relationship with the group, we may hold:

- names, dates of birth, addresses and contact details for young people, parents, carers, volunteers and enquirers
- membership, section, attendance, waiting-list and activity information
- emergency contacts and information needed to support health, dietary, accessibility, faith or additional needs
- volunteer role, training, qualification and safeguarding information
- photographs, video and stories where the appropriate permissions are in place
- contact-form messages and joining requests
- leader-account details, permissions, security records and administrative audit history
- technical information needed to secure and operate the website

Health, disability, faith and similar information may be special-category data. We collect it only when it is necessary to provide safe, inclusive Scouting and an appropriate legal condition applies.

## Why we use personal information

We use personal information to provide and administer Scouting, manage membership and waiting lists, communicate with families and volunteers, plan safe activities, respond to enquiries, support inclusion, manage volunteers, meet safeguarding and legal responsibilities, protect the website and maintain accurate records.

Our legal bases may include legitimate interests in running safe and effective local Scouting, performance of an agreement, compliance with legal obligations, protection of vital interests and consent where consent is the appropriate basis. We do not use consent where another legal basis is more suitable, and consent may be withdrawn at any time where it is relied upon.

## Sharing information

Access is limited to authorised volunteers who need the information for their role. We may share relevant information with The Scout Association, the District, County or other Scout units; Online Scout Manager; activity providers; insurers; professional advisers; website, email and hosting suppliers; and public authorities where this is necessary and lawful.

We do not sell personal information. When information is processed outside the UK, we require an appropriate legal safeguard or another lawful transfer mechanism.

## Retention and accuracy

We keep information only for as long as it is needed for its purpose and any legal, safeguarding, insurance or accounting requirement. Records are reviewed and securely deleted or anonymised when no longer required. Please tell us when your details change so that our records remain accurate.

## Security

We use role-based access, separate administrator permission, account verification, two-factor authentication where available, audit records, encrypted connections, backups and trusted service providers to protect information. Volunteers must keep account credentials private and report suspected loss, misuse or unauthorised access promptly.

## Your rights

Depending on the circumstances, you may ask for access to your information, correction, deletion, restriction, transfer, or object to how it is used. You may also withdraw consent where processing is based on consent. Some rights are limited where the group must retain or use information for legal, safeguarding or other compelling reasons.

Contact the group first so we can investigate. You also have the right to complain to the [Information Commissioner's Office](https://ico.org.uk/make-a-complaint/).

## Related Scouts policy

This local policy is adapted from the [Scouts UK Data Protection Policy](https://www.scouts.org.uk/about-us/policy/data-protection-policy/), which explains the wider approach used by The Scout Association and local Scouting. This policy should be reviewed by the Group Trustee Board whenever the group's processing changes.
MARKDOWN;
    }

    private function cookiePolicyContent(): string
    {
        return <<<'MARKDOWN'
## What cookies are

Cookies are small text files stored by your browser. Websites use them to remember information between requests, provide secure sign-in and retain preferences. This policy also covers browser storage used for the same kinds of functional preferences.

## Cookies and storage used by this website

The {{ group_name }} website currently uses only functional storage:

- **Session cookie:** keeps the website session working, including signed-in leader and administrator sessions
- **XSRF-TOKEN:** protects forms and account actions against cross-site request forgery
- **appearance:** remembers the light, dark or system colour preference for up to one year; the same preference is also kept in browser local storage
- **sidebar_state:** remembers whether the administration sidebar is open for up to seven days
- **news_post_access:** remembers successful access to a password-protected news article for one hour

These items are needed to provide a requested feature, maintain security or remember a preference. The website does not currently use analytics, advertising or social-media tracking cookies.

## Cloudflare Turnstile

Contact and joining forms use Cloudflare Turnstile to distinguish genuine submissions from automated abuse. The Turnstile script is loaded only where the security check is displayed. Cloudflare may process technical information and use necessary storage as described in its own [Turnstile privacy documentation](https://www.cloudflare.com/privacypolicy/).

## Third-party websites

Links may take you to Scouts, Online Scout Manager, the Scout Shop, the charity register or other external services. Those websites control their own cookies and publish their own cookie or privacy notices. Their use of cookies is not covered by this local policy.

## Managing cookies

You can inspect, block or delete cookies using your browser settings. Blocking session or security cookies may prevent sign-in, forms and protected content from working. Clearing preference storage resets the website to its default appearance and layout.

The [Information Commissioner's Office cookie guidance](https://ico.org.uk/for-the-public/online/cookies/) explains how to manage cookies and similar technologies.

## Changes and contact

We will update this page before introducing any optional analytics or advertising cookies. Use the contact page if you have a question about storage used by this website.

This page is adapted for the local website from the [Scouts UK Cookie Policy](https://www.scouts.org.uk/about-us/policy/cookie-policy/). Scouts UK's policy applies to services operated directly by The Scout Association.
MARKDOWN;
    }

    private function termsContent(): string
    {
        return <<<'MARKDOWN'
## About these terms

These terms apply when you use the {{ group_name }} website, including its public pages and any services available to registered volunteers. By continuing to use the website, you agree to follow these terms. We may update them when the website, the law or relevant Scouts policies change.

## Using the website

Use the website only for lawful purposes and in a way that does not harm the group, Scouts, other users or the operation of the service. You must not attempt to gain unauthorised access, introduce malicious code, disrupt the website, impersonate another person or use information obtained here for an unrelated purpose.

## Information and availability

We take reasonable care to keep meeting details, news, events and joining information accurate. Activities, places and arrangements can change, so confirm important details with a section leader. The website is provided on an as-available basis and may occasionally be unavailable or contain errors.

## Volunteer accounts

Accounts are personal to the approved volunteer. Keep passwords and recovery codes private, use two-factor authentication where available and tell an administrator promptly if access may have been compromised. {{ group_name }} may restrict or remove access when a volunteer leaves, breaches these terms, or when this is necessary to protect young people, personal information or the service. Having a volunteer account does not by itself grant access to the administration area; that requires separate administrator permission.

## Contributions and personal information

Only submit material that you have permission to use. When providing photographs, video, names or other information about an identifiable person, make sure the appropriate consent and safeguarding requirements have been met. Do not publish a young person's personal contact details or sensitive information. Content submitted for publication may be edited, declined or removed by authorised volunteers.

Registered volunteers must handle personal information only for authorised Scouting purposes and in line with data-protection law, safeguarding requirements and current Scouts policies. Do not download, share or retain personal information unless it is needed for your role.

## Intellectual property

Unless stated otherwise, content created by {{ group_name }} may not be copied, republished or used commercially without permission. The Scouts name, fleur-de-lis and other Scouts branding are protected and must be used in accordance with Scouts brand guidance. Third-party names, logos and material remain the property of their respective owners.

## External websites and services

Links and integrations may lead to services operated by Scouts, Online Scout Manager, Cloudflare or other providers. {{ group_name }} does not control those services, and their own terms and privacy notices apply.

## Responsibility

Nothing in these terms excludes responsibility that cannot lawfully be excluded. To the extent permitted by law, {{ group_name }} is not responsible for losses caused by relying on outdated public information, temporary unavailability, or third-party websites and services outside the group's control.

## Governing law

These terms are governed by the law of England and Wales. The courts of England and Wales have jurisdiction over disputes relating to the website.

## Contact and official Scouts terms

Use the contact page to report an error, accessibility issue, security concern or question about these terms.

These local terms are adapted from the [Scouts UK website terms and conditions](https://www.scouts.org.uk/about-us/policy/terms-conditions/). The official Scouts terms also apply when you use services operated directly by The Scout Association.
MARKDOWN;
    }

    /**
     * @param  list<array{block_type: class-string, data: array<string, mixed>}>  $blocks
     * @return array{title: string, slug: string, blocks: list<array{block_type: class-string, data: array<string, mixed>}>}
     */
    private function page(string $title, string $slug, array $blocks): array
    {
        return [
            'title' => $title,
            'slug' => $slug,
            'blocks' => $blocks,
        ];
    }

    /**
     * @return array{block_type: class-string, data: array<string, mixed>}
     */
    private function hero(
        string $eyebrow,
        string $title,
        string $body,
        string $image,
        ?string $label = null,
        ?string $url = null,
    ): array {
        return [
            'block_type' => HeroBlock::class,
            'data' => [
                'eyebrow' => $eyebrow,
                'title' => $title,
                'body' => $body,
                'image' => $image,
                'primary_label' => $label,
                'primary_url' => $url,
            ],
        ];
    }

    /**
     * @return array{block_type: class-string, data: array<string, mixed>}
     */
    private function richText(string $content): array
    {
        return [
            'block_type' => RichTextBlock::class,
            'data' => [
                'content' => $content,
            ],
        ];
    }

    /**
     * @return array{block_type: class-string, data: array<string, mixed>}
     */
    private function imageText(
        string $title,
        string $content,
        string $image,
        string $position = 'left',
    ): array {
        return [
            'block_type' => ImageTextBlock::class,
            'data' => [
                'title' => $title,
                'content' => $content,
                'image' => $image,
                'image_position' => $position,
                'image_width' => 'one-half',
            ],
        ];
    }

    /**
     * @return array{block_type: class-string, data: array<string, mixed>}
     */
    private function sectionLeaders(string $section): array
    {
        return [
            'block_type' => SectionLeadersBlock::class,
            'data' => [
                'section' => $section,
                'eyebrow' => 'Meet the team',
                'title' => "Meet our {$section} leaders",
                'intro' => "Our {$section} leaders are volunteers who create a safe, welcoming and adventurous programme every week.",
            ],
        ];
    }

    /**
     * @return array{block_type: class-string, data: array<string, mixed>}
     */
    private function cta(string $title, string $body, string $label, string $url): array
    {
        return [
            'block_type' => CtaBlock::class,
            'data' => [
                'title' => $title,
                'body' => $body,
                'button_label' => $label,
                'button_url' => $url,
            ],
        ];
    }

    /**
     * @param  list<array{title: string, body: string}>  $cards
     * @return array{block_type: class-string, data: array<string, mixed>}
     */
    private function contactDetails(
        string $title,
        string $intro,
        array $cards,
        ?string $primaryLabel = null,
        ?string $primaryUrl = null,
        ?string $secondaryLabel = null,
        ?string $secondaryUrl = null,
    ): array {
        return [
            'block_type' => ContactDetailsBlock::class,
            'data' => [
                'eyebrow' => 'Plan your first visit',
                'title' => $title,
                'intro' => $intro,
                'cards' => $cards,
                'primary_label' => $primaryLabel,
                'primary_url' => $primaryUrl,
                'secondary_label' => $secondaryLabel,
                'secondary_url' => $secondaryUrl,
            ],
        ];
    }

    /**
     * @return array{block_type: class-string, data: array<string, mixed>}
     */
    private function contactForm(string $title, string $intro): array
    {
        return [
            'block_type' => ContactFormBlock::class,
            'data' => [
                'eyebrow' => 'Contact form',
                'title' => $title,
                'intro' => $intro,
                'submit_label' => 'Send message',
            ],
        ];
    }

    /**
     * @return array{block_type: class-string, data: array<string, mixed>}
     */
    private function googleMap(string $title, string $intro): array
    {
        return [
            'block_type' => GoogleMapBlock::class,
            'data' => [
                'eyebrow' => 'Google map',
                'title' => $title,
                'intro' => $intro,
            ],
        ];
    }
}
