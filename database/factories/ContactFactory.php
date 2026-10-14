<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'permanent_address' => fake()->address(),
            'current_address' => fake()->address(),
            'phone_number' => $this->generatePakistaniPhone(),
            'mobile_number' => $this->generatePakistaniMobile(),
            'emergency_contact' => $this->generatePakistaniMobile(),
            'email' => fake()->unique()->safeEmail(),
        ];
    }

    /**
     * Generate a Pakistani landline phone number.
     */
    protected function generatePakistaniPhone(): string
    {
        $areaCodes = ['042', '051', '021', '041', '061'];
        return fake()->randomElement($areaCodes) . '-' . fake()->numerify('#######');
    }

    /**
     * Generate a Pakistani mobile number.
     */
    protected function generatePakistaniMobile(): string
    {
        $prefixes = ['0300', '0301', '0302', '0303', '0304', '0305', '0321', '0322', '0323', '0333', '0334', '0335'];
        return fake()->randomElement($prefixes) . '-' . fake()->numerify('#######');
    }
}
