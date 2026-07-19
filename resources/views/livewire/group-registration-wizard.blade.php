<div>
    <div class="wizard-steps">
        @foreach (['Personal Details', 'Solo / Group', 'Group Members', 'Review', 'Confirmation'] as $i => $label)
            <div class="wizard-step {{ $step === $i + 1 ? 'is-current' : '' }} {{ $step > $i + 1 ? 'is-done' : '' }}">
                {{ $i + 1 }}. {{ $label }}
            </div>
        @endforeach
    </div>

    <div class="panel">
        <div class="panel-body">
            {{-- Step 1: Personal details --}}
            @if ($step === 1)
                <h2>Personal Details</h2>

                <div class="grid-2">
                    <div class="field">
                        <x-input-label for="first_name" value="First Name" />
                        <input type="text" id="first_name" wire:model="first_name">
                        <x-input-error :messages="$errors->get('first_name')" />
                    </div>

                    <div class="field">
                        <x-input-label for="last_name" value="Last Name" />
                        <input type="text" id="last_name" wire:model="last_name">
                        <x-input-error :messages="$errors->get('last_name')" />
                    </div>

                    <div class="field">
                        <x-input-label for="date_of_birth" value="Date of Birth" />
                        <input type="date" id="date_of_birth" wire:model="date_of_birth">
                        <x-input-error :messages="$errors->get('date_of_birth')" />
                    </div>

                    <div class="field">
                        <x-input-label for="gender" value="Gender" />
                        <select id="gender" wire:model="gender">
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                        <x-input-error :messages="$errors->get('gender')" />
                    </div>

                    <div class="field">
                        <x-input-label for="phone" value="Phone" />
                        <input type="tel" id="phone" wire:model="phone">
                        <x-input-error :messages="$errors->get('phone')" />
                    </div>

                    <div class="field">
                        <x-input-label for="language" value="Preferred Language" />
                        <input type="text" id="language" wire:model="language">
                    </div>

                    <div class="field">
                        <x-input-label for="passport_number" value="Passport Number (optional)" />
                        <input type="text" id="passport_number" wire:model="passport_number">
                    </div>

                    <div class="field">
                        <x-input-label for="adhar_id" value="Aadhaar Number (optional)" />
                        <input type="text" id="adhar_id" wire:model="adhar_id" maxlength="12">
                        <x-input-error :messages="$errors->get('adhar_id')" />
                    </div>

                    <div class="field">
                        <x-input-label for="father_name" value="Father's Name" />
                        <input type="text" id="father_name" wire:model="father_name">
                    </div>

                    <div class="field">
                        <x-input-label for="mother_name" value="Mother's Name" />
                        <input type="text" id="mother_name" wire:model="mother_name">
                    </div>

                    <div class="field">
                        <x-input-label for="city" value="City" />
                        <input type="text" id="city" wire:model="city">
                    </div>

                    <div class="field">
                        <x-input-label for="state" value="State" />
                        <input type="text" id="state" wire:model="state">
                    </div>

                    <div class="field">
                        <x-input-label for="country" value="Country" />
                        <input type="text" id="country" wire:model="country">
                    </div>

                    <div class="field">
                        <x-input-label for="luggage_count" value="Luggage Count" />
                        <input type="number" id="luggage_count" wire:model="luggage_count" min="0">
                    </div>
                </div>

                <div class="field">
                    <x-input-label for="address" value="Address" />
                    <textarea id="address" wire:model="address" rows="2"></textarea>
                </div>

                <div class="form-row-end">
                    <button type="button" class="btn btn-primary" wire:click="proceedToStep2">Next</button>
                </div>
            @endif

            {{-- Step 2: Trip details, Solo vs Group --}}
            @if ($step === 2)
                <h2>Plan Your Trip</h2>

                <div class="grid-2">
                    <div class="field">
                        <x-input-label for="trip_start_date" value="Trip Start Date" />
                        <input type="date" id="trip_start_date" wire:model="trip_start_date">
                        <x-input-error :messages="$errors->get('trip_start_date')" />
                    </div>

                    <div class="field">
                        <x-input-label for="trip_end_date" value="Trip End Date" />
                        <input type="date" id="trip_end_date" wire:model="trip_end_date">
                        <x-input-error :messages="$errors->get('trip_end_date')" />
                    </div>
                </div>

                <h2 style="margin-top: 1.5rem;">Are you registering solo or as a group?</h2>

                <div class="field" style="margin-top: 1rem;">
                    <label class="checkbox-label">
                        <input type="radio" wire:model.live="registrationType" value="solo">
                        <span>Solo — just me</span>
                    </label>
                </div>
                <div class="field">
                    <label class="checkbox-label">
                        <input type="radio" wire:model.live="registrationType" value="group">
                        <span>Group — me and others travelling together</span>
                    </label>
                </div>

                @if ($registrationType === 'group')
                    <div class="field">
                        <x-input-label for="groupName" value="Group Name" />
                        <input type="text" id="groupName" wire:model="groupName" placeholder="e.g. Sharma Family Group">
                        <x-input-error :messages="$errors->get('groupName')" />
                    </div>

                    <div class="field">
                        <x-input-label for="expected_member_count" value="Total Group Size (including you)" />
                        <input type="number" id="expected_member_count" wire:model="expected_member_count" min="2" max="20">
                        <x-input-error :messages="$errors->get('expected_member_count')" />
                    </div>
                @endif

                <div class="form-row-end">
                    <button type="button" class="btn btn-secondary" wire:click="goToStep1">Back</button>
                    <button type="button" class="btn btn-primary" wire:click="proceedToStep3" wire:loading.attr="disabled">
                        {{ $registrationType === 'group' ? 'Next' : 'Preview Allocation' }}
                    </button>
                </div>
            @endif

            {{-- Step 3: Group members --}}
            @if ($step === 3)
                <h2>Group Members</h2>
                <p class="text-muted">Add each person travelling in your group and declare how they relate to someone already in the list.</p>

                @foreach ($members as $index => $member)
                    <div class="member-row" wire:key="member-{{ $index }}">
                        <div class="member-row-header">
                            <span class="member-row-title">Member {{ $index + 1 }}</span>
                            <button type="button" class="btn btn-danger" wire:click="removeMember({{ $index }})">Remove</button>
                        </div>

                        <div class="grid-2">
                            <div class="field">
                                <x-input-label value="First Name" />
                                <input type="text" wire:model="members.{{ $index }}.first_name">
                                <x-input-error :messages="$errors->get('members.'.$index.'.first_name')" />
                            </div>

                            <div class="field">
                                <x-input-label value="Last Name" />
                                <input type="text" wire:model="members.{{ $index }}.last_name">
                                <x-input-error :messages="$errors->get('members.'.$index.'.last_name')" />
                            </div>

                            <div class="field">
                                <x-input-label value="Date of Birth" />
                                <input type="date" wire:model="members.{{ $index }}.date_of_birth">
                                <x-input-error :messages="$errors->get('members.'.$index.'.date_of_birth')" />
                            </div>

                            <div class="field">
                                <x-input-label value="Gender" />
                                <select wire:model="members.{{ $index }}.gender">
                                    <option value="">Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                <x-input-error :messages="$errors->get('members.'.$index.'.gender')" />
                            </div>

                            <div class="field">
                                <x-input-label value="Related To" />
                                <select wire:model="members.{{ $index }}.related_index">
                                    <option value="">Select</option>
                                    <option value="-1">You ({{ $first_name }} {{ $last_name }})</option>
                                    @foreach ($members as $priorIndex => $priorMember)
                                        @if ($priorIndex < $index)
                                            <option value="{{ $priorIndex }}">
                                                {{ $priorMember['first_name'] ?: 'Member '.($priorIndex + 1) }} {{ $priorMember['last_name'] }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <div class="field">
                                <x-input-label value="Relation" />
                                <select wire:model="members.{{ $index }}.relation_type">
                                    <option value="">Select</option>
                                    @foreach ($this->relationOptions() as $relation)
                                        <option value="{{ $relation }}">{{ $relation }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('members.'.$index.'.relation_type')" />
                            </div>
                        </div>
                    </div>
                @endforeach

                <button type="button" class="btn btn-secondary" wire:click="addMember">+ Add Another Member</button>

                <div class="form-row-end">
                    <button type="button" class="btn btn-secondary" wire:click="$set('step', 2)">Back</button>
                    <button type="button" class="btn btn-primary" wire:click="proceedToStep4" wire:loading.attr="disabled">
                        Preview Allocation
                    </button>
                </div>
            @endif

            {{-- Step 4a: Review your details (confirmation before the engine runs) --}}
            @if ($step === 4 && ! $showPreview)
                <h2>Review Your Details</h2>
                <p class="text-muted">Please check everything below. You can go back and change anything before we generate your room preview.</p>

                <div class="cluster-card">
                    <div class="member-row-header">
                        <span class="member-row-title">Personal Details</span>
                        <button type="button" class="btn btn-secondary" wire:click="goToStep1">Edit</button>
                    </div>
                    <ul class="member-list">
                        <li>{{ trim("{$first_name} {$last_name}") }} — {{ ucfirst($gender) }}, born {{ $date_of_birth }}</li>
                        <li>Phone: {{ $phone }}{{ $city ? ', '.$city : '' }}</li>
                    </ul>
                </div>

                <div class="cluster-card">
                    <div class="member-row-header">
                        <span class="member-row-title">Trip &amp; Registration</span>
                        <button type="button" class="btn btn-secondary" wire:click="$set('step', 2)">Edit</button>
                    </div>
                    <ul class="member-list">
                        <li>{{ ucfirst($registrationType) }} registration{{ $registrationType === 'group' ? ' — '.$groupName : '' }}</li>
                        <li>Trip: {{ $trip_start_date }} &rarr; {{ $trip_end_date }}</li>
                    </ul>
                </div>

                @if ($registrationType === 'group')
                    <div class="cluster-card">
                        <div class="member-row-header">
                            <span class="member-row-title">Group Members ({{ count($members) + 1 }})</span>
                            <button type="button" class="btn btn-secondary" wire:click="backToStep3">Edit</button>
                        </div>
                        <ul class="member-list">
                            <li>{{ trim("{$first_name} {$last_name}") }} (You)</li>
                            @foreach ($members as $member)
                                <li>
                                    {{ trim(($member['first_name'] ?? '').' '.($member['last_name'] ?? '')) }}
                                    {{ ! empty($member['relation_type']) ? '('.$member['relation_type'].')' : '' }}
                                    {{ ! empty($member['gender']) ? '— '.ucfirst($member['gender']) : '' }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="form-row-end">
                    <button type="button" class="btn btn-secondary" wire:click="backFromReview">Back</button>
                    <button type="button" class="btn btn-primary" wire:click="previewAllocation" wire:loading.attr="disabled">
                        Confirm &amp; Preview Allocation
                    </button>
                </div>
            @endif

            {{-- Step 4b: live allocation preview --}}
            @if ($step === 4 && $showPreview)
                <h2>Review &amp; Allocation Preview</h2>
                <p class="text-muted">This is a live preview from the allocation engine. An admin will confirm your final rooms.</p>

                @php $roomAssignments = $this->roomAssignments(); @endphp

                @if ($roomAssignments->isNotEmpty())
                    <h3 style="margin-top: 1.5rem;">Room Assignments</h3>

                    <div class="room-grid">
                        @foreach ($roomAssignments as $assignment)
                            @php $room = $assignment['room']; @endphp
                            <div class="room-card">
                                <div class="room-card-header">
                                    <span class="room-card-number">Room {{ $room->room_number }}</span>
                                    <span class="badge badge-muted">{{ ucfirst($room->room_type) }}</span>
                                </div>
                                <p class="text-muted room-card-hotel">{{ $room->hotel->hotel_name }}</p>

                                <ul class="member-list">
                                    @foreach ($assignment['members'] as $member)
                                        <li>{{ $member->name }}</li>
                                    @endforeach
                                </ul>

                                <p class="text-muted room-card-capacity">
                                    {{ $assignment['members']->count() }} / {{ $room->capacity }} occupied
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif

                <h3 style="margin-top: 1.5rem;">By Family / Cluster</h3>

                @foreach ($previewResults as $result)
                    @php $cluster = $result['cluster']; @endphp
                    <div class="cluster-card {{ $result['status'] === 'blocked' ? 'is-blocked' : ($result['status'] === 'allocated' ? 'is-allocated' : '') }}">
                        <div class="member-row-header">
                            <span class="member-row-title">{{ $cluster->cluster_name }}</span>
                            @if ($result['status'] === 'allocated')
                                <span class="badge badge-success">Allocated</span>
                            @elseif ($result['status'] === 'blocked')
                                <span class="badge badge-danger">Needs Admin Review</span>
                            @else
                                <span class="badge badge-muted">Awaiting Room</span>
                            @endif
                        </div>

                        @if ($result['reason'])
                            <p class="text-muted">{{ $result['reason'] }}</p>
                        @endif

                        <ul class="member-list">
                            @foreach ($cluster->members as $member)
                                @php
                                    $allocation = $cluster->allocations->firstWhere('user_id', $member->user_id);
                                @endphp
                                <li>
                                    {{ $member->user->name }} ({{ $member->relation_type }})
                                    @if ($allocation)
                                        — Room {{ $allocation->room->room_number }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach

                <div class="form-row-end">
                    <button type="button" class="btn btn-secondary" wire:click="backToConfirmation">Back</button>
                    <button type="button" class="btn btn-primary" wire:click="confirmSubmission">Confirm &amp; Submit</button>
                </div>
            @endif

            {{-- Step 5: Confirmation --}}
            @if ($step === 5)
                <h2>Registration Submitted</h2>
                <p class="text-muted">Your registration has been submitted and is awaiting final confirmation from our team.</p>

                <div class="booking-id">{{ $bookingId }}</div>

                <p class="text-muted">Keep this booking ID for reference. You'll be notified once your rooms are confirmed.</p>

                <div class="form-row-end">
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
                </div>
            @endif
        </div>
    </div>
</div>
