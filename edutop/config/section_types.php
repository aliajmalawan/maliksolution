<?php

/**
 * Section-type registry. Every page is composed from these reusable,
 * form-editable block types instead of one-off types per page. This array
 * drives both the admin section-editor form generator (App\Core\SectionForm)
 * and the public renderer (App\Services\PageRenderer), which looks up
 * Views/public/sections/{type}.php for each section.
 *
 * Supported field types: text, textarea, richtext, media, number, checkbox,
 * select (needs 'options'), repeater (needs 'fields' of its own).
 */
return [
    'slider' => [
        'label' => 'Hero Slider (full-width carousel)',
        'fields' => [
            'autoplay_ms' => ['type' => 'number', 'label' => 'Autoplay interval in milliseconds (e.g. 6000; 0 disables autoplay)'],
            'slides' => [
                'type' => 'repeater',
                'label' => 'Slides',
                'fields' => [
                    'image' => ['type' => 'media', 'label' => 'Background Image', 'recommended_size' => '1920×900px'],
                    'eyebrow' => ['type' => 'text', 'label' => 'Small Label (optional, shown above heading)'],
                    'heading' => ['type' => 'text', 'label' => 'Heading'],
                    'subheading' => ['type' => 'textarea', 'label' => 'Subheading'],
                    'button_text' => ['type' => 'text', 'label' => 'Button Text'],
                    'button_url' => ['type' => 'text', 'label' => 'Button URL (use #demo-modal to open the Campus Visit popup)'],
                    'button2_text' => ['type' => 'text', 'label' => 'Second Button Text (optional)'],
                    'button2_url' => ['type' => 'text', 'label' => 'Second Button URL'],
                ],
            ],
        ],
    ],
    'hero' => [
        'label' => 'Hero Banner',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'subheading' => ['type' => 'textarea', 'label' => 'Subheading'],
            'image' => ['type' => 'media', 'label' => 'Image', 'recommended_size' => '1920×800px'],
            'button_text' => ['type' => 'text', 'label' => 'Button Text'],
            'button_url' => ['type' => 'text', 'label' => 'Button URL (use #demo-modal to open the Campus Visit popup)'],
        ],
    ],
    'rich_text' => [
        'label' => 'Rich Text',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading (optional)'],
            'content' => ['type' => 'richtext', 'label' => 'Content'],
        ],
    ],
    'page_header' => [
        'label' => 'Page Header (gradient title band with breadcrumb)',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'subtext' => ['type' => 'textarea', 'label' => 'Subtext (optional)'],
        ],
    ],
    'programs' => [
        'label' => 'Academic Programs (journey timeline)',
        'fields' => [
            'eyebrow' => ['type' => 'text', 'label' => 'Eyebrow Label (small badge above the heading, optional)'],
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'subheading' => ['type' => 'textarea', 'label' => 'Subheading'],
            'items' => [
                'type' => 'repeater',
                'label' => 'Program Stages (shown as a connected journey, in order)',
                'fields' => [
                    'icon' => ['type' => 'text', 'label' => 'Icon (emoji)'],
                    'title' => ['type' => 'text', 'label' => 'Title'],
                    'badge' => ['type' => 'text', 'label' => 'Small Badge (e.g. "Nursery – KG", optional)'],
                    'description' => ['type' => 'textarea', 'label' => 'Description'],
                ],
            ],
        ],
    ],
    'icon_grid' => [
        'label' => 'Icon Grid (Features / Why Choose Us / Modules)',
        'fields' => [
            'eyebrow' => ['type' => 'text', 'label' => 'Eyebrow Label (small badge above the heading, optional)'],
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'subheading' => ['type' => 'textarea', 'label' => 'Subheading'],
            'items' => [
                'type' => 'repeater',
                'label' => 'Items',
                'fields' => [
                    'icon' => ['type' => 'text', 'label' => 'Icon (emoji or CSS class)'],
                    'title' => ['type' => 'text', 'label' => 'Title'],
                    'description' => ['type' => 'textarea', 'label' => 'Description'],
                ],
            ],
        ],
    ],
    'stats' => [
        'label' => 'Statistics',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading (optional)'],
            'items' => [
                'type' => 'repeater',
                'label' => 'Stats',
                'fields' => [
                    'value' => ['type' => 'text', 'label' => 'Value (e.g. 500+)'],
                    'label' => ['type' => 'text', 'label' => 'Label'],
                ],
            ],
        ],
    ],
    'testimonials' => [
        'label' => 'Testimonials',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'display' => [
                'type' => 'select',
                'label' => 'Layout',
                'options' => ['grid' => 'Grid of cards', 'slider' => 'Sliding carousel'],
            ],
            'items' => [
                'type' => 'repeater',
                'label' => 'Testimonials',
                'fields' => [
                    'photo' => ['type' => 'media', 'label' => 'Photo', 'recommended_size' => '200×200px'],
                    'name' => ['type' => 'text', 'label' => 'Name'],
                    'role' => ['type' => 'text', 'label' => 'Role / Company'],
                    'quote' => ['type' => 'textarea', 'label' => 'Quote'],
                    'rating' => ['type' => 'number', 'label' => 'Rating (1-5)'],
                ],
            ],
        ],
    ],
    'clients' => [
        'label' => 'Clients / Logos',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'items' => [
                'type' => 'repeater',
                'label' => 'Logos',
                'fields' => [
                    'logo' => ['type' => 'media', 'label' => 'Logo', 'recommended_size' => '300×150px'],
                    'name' => ['type' => 'text', 'label' => 'Name'],
                    'url' => ['type' => 'text', 'label' => 'Link URL'],
                ],
            ],
        ],
    ],
    'pricing' => [
        'label' => 'Pricing Plans',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'items' => [
                'type' => 'repeater',
                'label' => 'Plans',
                'fields' => [
                    'plan_name' => ['type' => 'text', 'label' => 'Plan Name'],
                    'price' => ['type' => 'text', 'label' => 'Price'],
                    'period' => ['type' => 'text', 'label' => 'Billing Period (e.g. /month)'],
                    'features' => ['type' => 'textarea', 'label' => 'Features (one per line)'],
                    'button_text' => ['type' => 'text', 'label' => 'Button Text'],
                    'button_url' => ['type' => 'text', 'label' => 'Button URL'],
                    'is_featured' => ['type' => 'checkbox', 'label' => 'Highlight this plan'],
                ],
            ],
        ],
    ],
    'faq' => [
        'label' => 'FAQ',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'items' => [
                'type' => 'repeater',
                'label' => 'Questions',
                'fields' => [
                    'question' => ['type' => 'text', 'label' => 'Question'],
                    'answer' => ['type' => 'textarea', 'label' => 'Answer'],
                ],
            ],
        ],
    ],
    'cta' => [
        'label' => 'Call To Action',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'subtext' => ['type' => 'textarea', 'label' => 'Subtext'],
            'button_text' => ['type' => 'text', 'label' => 'Button Text'],
            'button_url' => ['type' => 'text', 'label' => 'Button URL (use #demo-modal to open the Campus Visit popup)'],
            'background' => ['type' => 'media', 'label' => 'Background Image', 'recommended_size' => '1920×600px'],
        ],
    ],
    'gallery' => [
        'label' => 'Gallery / Team',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'items' => [
                'type' => 'repeater',
                'label' => 'Items',
                'fields' => [
                    'image' => ['type' => 'media', 'label' => 'Image', 'recommended_size' => '600×450px'],
                    'title' => ['type' => 'text', 'label' => 'Title (e.g. name)'],
                    'subtitle' => ['type' => 'text', 'label' => 'Subtitle (e.g. role)'],
                    'description' => ['type' => 'textarea', 'label' => 'Description'],
                ],
            ],
        ],
    ],
    'image_gallery' => [
        'label' => 'Photo Gallery (images only, no captions)',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading (optional)'],
            'items' => [
                'type' => 'repeater',
                'label' => 'Photos',
                'fields' => [
                    'image' => ['type' => 'media', 'label' => 'Image', 'recommended_size' => '600×450px'],
                    'category' => ['type' => 'text', 'label' => 'Category (optional — e.g. Sports, Events, Campus. Type a new name to create it, or reuse an existing one to add to it.)'],
                ],
            ],
        ],
    ],
    'map' => [
        'label' => 'Google Map',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading (optional)'],
            'embed_url' => ['type' => 'text', 'label' => 'Google Maps Embed URL'],
        ],
    ],
    'image_text' => [
        'label' => 'Image + Text',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'text' => ['type' => 'richtext', 'label' => 'Text'],
            'image' => ['type' => 'media', 'label' => 'Image', 'recommended_size' => '800×600px'],
            'image_position' => [
                'type' => 'select',
                'label' => 'Image Position',
                'options' => ['left' => 'Left', 'right' => 'Right'],
            ],
            'button_text' => ['type' => 'text', 'label' => 'Button Text'],
            'button_url' => ['type' => 'text', 'label' => 'Button URL'],
        ],
    ],
    'admission_form' => [
        'label' => 'Admission Form (online application)',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'subtext' => ['type' => 'textarea', 'label' => 'Subtext'],
            'fields' => [
                'type' => 'repeater',
                'label' => 'Form Fields (shown in this order on the public form; add a "Section Heading" item to start a new visual group, e.g. "Student Information")',
                'fields' => [
                    'field_type' => [
                        'type' => 'select',
                        'label' => 'Field Type',
                        'options' => [
                            'text' => 'Text',
                            'tel' => 'Phone Number',
                            'email' => 'Email',
                            'date' => 'Date',
                            'textarea' => 'Textarea (multi-line)',
                            'select' => 'Dropdown',
                            'radio' => 'Radio Buttons',
                            'checkbox' => 'Checkbox (single, e.g. an agreement)',
                            'heading' => 'Section Heading (visual group break — not a real field)',
                        ],
                    ],
                    'label' => ['type' => 'text', 'label' => 'Label (question text, or the section heading text)'],
                    'name' => ['type' => 'text', 'label' => 'Field Name (unique; letters/numbers/underscore, e.g. student_name — ignored for Section Heading)'],
                    'options' => ['type' => 'textarea', 'label' => 'Options (Dropdown / Radio Buttons only — one per line)'],
                    'placeholder' => ['type' => 'text', 'label' => 'Placeholder (optional)'],
                    'required' => ['type' => 'checkbox', 'label' => 'Required'],
                    'width' => [
                        'type' => 'select',
                        'label' => 'Column Width',
                        'options' => ['12' => 'Full width', '6' => 'Half width', '4' => 'One third'],
                    ],
                    'map_to' => [
                        'type' => 'select',
                        'label' => 'Use as contact… (so it shows in the admin Leads list)',
                        'options' => ['' => '— none —', 'name' => 'Applicant Name', 'email' => 'Email', 'phone' => 'Phone'],
                    ],
                ],
            ],
        ],
    ],
    'contact_form' => [
        'label' => 'Contact Form',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'subtext' => ['type' => 'textarea', 'label' => 'Subtext'],
        ],
    ],
];
