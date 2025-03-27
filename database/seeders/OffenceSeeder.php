<?php

namespace Database\Seeders;

use App\Models\Offence;
use Illuminate\Database\Seeder;

class OffenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cyber Security and Cyber Crimes Act, 2021 Offences
        $cyberOffences = [
            [
                'name' => 'Unauthorised Access to Computer Systems',
                'description' => 'Intentionally accessing or intercepting data without authority or permission',
                'source' => 'THE CYBER SECURITY AND CYBER CRIMES ACT, 2021',
                'section' => 'Section 49',
                'is_active' => true,
            ],
            [
                'name' => 'Computer-Related Misrepresentation',
                'description' => 'Knowingly inputting, altering, or suppressing computer data to produce unauthentic data',
                'source' => 'THE CYBER SECURITY AND CYBER CRIMES ACT, 2021',
                'section' => 'Section 51',
                'is_active' => true,
            ],
            [
                'name' => 'Identity-Related Crimes',
                'description' => 'Using a computer system to transfer, possess, or use another person\'s identification information without lawful excuse',
                'source' => 'THE CYBER SECURITY AND CYBER CRIMES ACT, 2021',
                'section' => 'Section 53',
                'is_active' => true,
            ],
            [
                'name' => 'Child Pornography',
                'description' => 'Producing, distributing, or possessing child pornography through a computer system',
                'source' => 'THE CYBER SECURITY AND CYBER CRIMES ACT, 2021',
                'section' => 'Section 57',
                'is_active' => true,
            ],
            [
                'name' => 'Cyber Extortion',
                'description' => 'Using a computer system to threaten others to extort money or gain advantages',
                'source' => 'THE CYBER SECURITY AND CYBER CRIMES ACT, 2021',
                'section' => 'Section 52',
                'is_active' => true,
            ],
            [
                'name' => 'Introduction of Malicious Software',
                'description' => 'Intentionally introducing or spreading malicious software into a computer system',
                'source' => 'THE CYBER SECURITY AND CYBER CRIMES ACT, 2021',
                'section' => 'Section 60',
                'is_active' => true,
            ],
        ];

        // Penal Code Offences
        $penalCodeOffences = [
            [
                'name' => 'Theft',
                'description' => 'Dishonestly appropriating property belonging to another with the intention to permanently deprive the owner',
                'source' => 'Penal code cap 87',
                'section' => 'Section 272',
                'is_active' => true,
            ],
            [
                'name' => 'Assault',
                'description' => 'Unlawfully applying force to another person',
                'source' => 'Penal code cap 87',
                'section' => 'Section 248',
                'is_active' => true,
            ],
            [
                'name' => 'Forgery',
                'description' => 'Making a false document with intent to defraud or deceive',
                'source' => 'Penal code cap 87',
                'section' => 'Section 342',
                'is_active' => true,
            ],
            [
                'name' => 'Obtaining Goods by False Pretenses',
                'description' => 'Obtaining goods, money, or advantages by false representation',
                'source' => 'Penal code cap 87',
                'section' => 'Section 309',
                'is_active' => true,
            ],
            [
                'name' => 'Defilement',
                'description' => 'Sexual intercourse with a child under 16 years of age',
                'source' => 'Penal code cap 87',
                'section' => 'Section 138',
                'is_active' => true,
            ],
            [
                'name' => 'Corruption',
                'description' => 'Public officers receiving bribes or rewards for performing or abstaining from official duties',
                'source' => 'Penal code cap 87',
                'section' => 'Section 98',
                'is_active' => true,
            ],
        ];

        // Anti-Terrorism Act Offences
        $antiTerrorismOffences = [
            [
                'name' => 'Terrorist Financing',
                'description' => 'Providing or collecting funds with the intention that they should be used to carry out a terrorist act',
                'source' => 'The Anti-Terrorism and Non-Proliferation (Amendment) Act, 2024 (Act No. 30 of 2024)',
                'section' => 'Section 22',
                'is_active' => true,
            ],
            [
                'name' => 'Terrorist Recruitment',
                'description' => 'Recruiting persons to be members of a terrorist group or to participate in terrorist acts',
                'source' => 'The Anti-Terrorism and Non-Proliferation (Amendment) Act, 2024 (Act No. 30 of 2024)',
                'section' => 'Section 25',
                'is_active' => true,
            ],
        ];

        // Combine all offences
        $allOffences = array_merge($cyberOffences, $penalCodeOffences, $antiTerrorismOffences);

        // Create offences in the database
        foreach ($allOffences as $offence) {
            Offence::create($offence);
        }
    }
}
