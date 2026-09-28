<x-filament-panels::page>
    <style>
        .chat-container { display: flex; height: 600px; border-radius: 0.75rem; border: 1px solid rgba(255,255,255,0.1); overflow: hidden; background-color: #0F1423; color: white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .chat-sidebar { width: 30%; min-width: 250px; border-right: 1px solid rgba(255,255,255,0.1); display: flex; flex-direction: column; background-color: rgba(255,255,255,0.02); }
        .chat-main { flex: 1; display: flex; flex-direction: column; background-color: #060913; }
        .chat-header { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); background-color: rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; }
        .chat-messages { flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: 1rem; }
        .chat-input-area { padding: 1rem; border-top: 1px solid rgba(255,255,255,0.1); background-color: rgba(255,255,255,0.05); }
        
        .msg-bubble { max-width: 75%; padding: 0.75rem 1rem; border-radius: 1rem; font-size: 0.875rem; line-height: 1.4; word-wrap: break-word; }
        .msg-user { align-self: flex-start; background-color: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.1); border-top-left-radius: 0.25rem; }
        .msg-agent { align-self: flex-end; background-color: #0284c7; color: white; border-top-right-radius: 0.25rem; }
        
        .convo-item { padding: 0.75rem; margin-bottom: 0.5rem; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s; border: 1px solid transparent; }
        .convo-item:hover { background-color: rgba(255,255,255,0.05); }
        .convo-active { background-color: rgba(2, 132, 199, 0.2); border-color: rgba(2, 132, 199, 0.5); }
        
        .giant-icon-fix { width: 4rem; height: 4rem; opacity: 0.5; margin-bottom: 1rem; }
        
        @keyframes custom-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; background-color: #ef4444; display: inline-block; animation: custom-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        
        .tab-container { display: flex; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .tab-btn { flex: 1; padding: 0.75rem; text-align: center; font-size: 0.875rem; font-weight: 600; cursor: pointer; color: #94a3b8; border-bottom: 2px solid transparent; transition: all 0.2s; }
        .tab-btn.active { color: #38bdf8; border-bottom-color: #0284c7; background-color: rgba(255,255,255,0.02); }
        .time-warning { color: #f59e0b; font-weight: 600; }
        .time-danger { color: #ef4444; font-weight: bold; }
    </style>

    <div class="chat-container">
        <!-- CỘT TRÁI: DANH SÁCH KHÁCH -->
        <div class="chat-sidebar">
            <div class="chat-header" style="padding-bottom: 0;">
                <h3 style="font-weight: bold; margin: 0; margin-bottom: 1rem;">Hỗ trợ khách hàng</h3>
            </div>
            
            <div class="tab-container">
                <div wire:click="$set('currentTab', 'waiting')" class="tab-btn {{ $currentTab === 'waiting' ? 'active' : '' }}">
                    Chờ tiếp nhận
                    @php 
                        $waitingCount = $conversations->where('status', 'WAITING_FOR_AGENT')->count(); 
                    @endphp
                    @if($waitingCount > 0)
                        <span style="background-color: #ef4444; color: white; padding: 0.1rem 0.4rem; border-radius: 1rem; font-size: 0.7rem; margin-left: 0.25rem;">{{ $waitingCount }}</span>
                    @endif
                </div>
                <div wire:click="$set('currentTab', 'mine')" class="tab-btn {{ $currentTab === 'mine' ? 'active' : '' }}">
                    Ca của tôi
                </div>
            </div>

            <div style="flex: 1; overflow-y: auto; padding: 0.5rem;" wire:poll.3s="loadConversations">
                @php
                    $filteredConvos = $currentTab === 'waiting' 
                        ? $conversations->where('status', 'WAITING_FOR_AGENT') 
                        : $conversations->where('status', 'AGENT_HANDLING')->where('assigned_to', auth()->id());
                @endphp

                @foreach($filteredConvos as $convo)
                    <div wire:click="selectConversation({{ $convo->id }})" class="convo-item {{ $activeConversation && $activeConversation->id === $convo->id ? 'convo-active' : '' }}">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                           <span style="font-weight: 600; font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 80%;">
                                {{ $convo->customer_name ? $convo->customer_name . ' - ' . $convo->customer_phone : $convo->title }}
                            </span>
                            @if($convo->status === 'WAITING_FOR_AGENT')
                                <span class="status-dot"></span>
                            @endif
                        </div>
                        
                        @php
                            $minutesWaiting = $convo->updated_at->diffInMinutes(now());
                            $timeClass = '';
                            if($convo->status === 'WAITING_FOR_AGENT') {
                                if($minutesWaiting >= 5) $timeClass = 'time-danger';
                                elseif($minutesWaiting >= 2) $timeClass = 'time-warning';
                            }
                        @endphp
                        <span style="font-size: 0.75rem; color: #94a3b8;" class="{{ $timeClass }}">
                            {{ $convo->updated_at->diffForHumans() }}
                        </span>
                    </div>
                @endforeach

                @if(count($filteredConvos) === 0)
                    <p style="text-align: center; font-size: 0.875rem; color: #64748b; margin-top: 2rem;">
                        {{ $currentTab === 'waiting' ? 'Tuyệt vời! Không có khách nào phải chờ.' : 'Bạn chưa nhận xử lý ca nào.' }}
                    </p>
                @endif
            </div>
        </div>

        <!-- CỘT PHẢI: KHUNG CHAT -->
        <div class="chat-main">
            @if($activeConversation)
                <div class="chat-header">
                 <h3 style="font-weight: bold; margin: 0;">
                        {{ $activeConversation->customer_name ? $activeConversation->customer_name . ' (' . $activeConversation->customer_phone . ')' : $activeConversation->title }}
                    </h3>
                    
                    @if($activeConversation->status === 'WAITING_FOR_AGENT')
                        <span style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background-color: rgba(239, 68, 68, 0.2); color: #f87171; border-radius: 0.25rem;">Khách đang chờ bạn gọi</span>
                    @else
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <span style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background-color: rgba(2, 132, 199, 0.2); color: #38bdf8; border-radius: 0.25rem;">Bạn đang xử lý</span>
                            
                            <button wire:click="closeConversation" wire:confirm="Bạn có chắc chắn đã hỗ trợ xong và muốn đóng ca này?" 
                                style="font-size: 0.75rem; padding: 0.25rem 0.75rem; background-color: #ef4444; color: white; border-radius: 0.25rem; border: none; cursor: pointer; font-weight: bold; transition: all 0.2s;">
                                ✖ Kết thúc
                            </button>
                        </div>
                    @endif
                </div>

                <div class="chat-messages" wire:poll.2s="loadMessages">
                    @foreach($messages as $msg)
                        <div class="msg-bubble {{ $msg->sender_type === 'user' ? 'msg-user' : 'msg-agent' }}">
                            {!! nl2br(e($msg->content)) !!}
                            
                            @if($msg->sender_type === 'ai')
                                <div style="font-size: 0.65rem; color: #bae6fd; margin-top: 0.4rem; font-style: italic; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.25rem;">
                                    ⚡ Trả lời tự động bởi AI
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="chat-input-area">
                    @if($activeConversation->status === 'WAITING_FOR_AGENT')
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1rem; background-color: rgba(239,68,68,0.1); border: 1px dashed #ef4444; border-radius: 0.5rem;">
                            <p style="margin-bottom: 0.75rem; font-size: 0.875rem; color: #fca5a5;">Khách hàng này đang chờ được hỗ trợ. Hãy tiếp nhận để bắt đầu chat!</p>
                            <button wire:click="assignToMe" style="padding: 0.75rem 2rem; background-color: #ef4444; color: white; border-radius: 9999px; font-weight: bold; border: none; cursor: pointer; transition: all 0.2s;">
                                Nhận ca này ngay
                            </button>
                        </div>
                    @else
                        <form wire:submit.prevent="sendMessage" style="display: flex; gap: 0.5rem;">
                            <input wire:model="newMessage" type="text" placeholder="Gõ câu trả lời cho khách hàng..." 
                                style="flex: 1; padding: 0.6rem 1.2rem; border-radius: 9999px; background-color: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; outline: none; font-size: 0.875rem;">
                            <button type="submit" style="padding: 0.5rem 1.5rem; background-color: #0284c7; color: white; border-radius: 9999px; font-weight: bold; border: none; cursor: pointer; font-size: 0.875rem;">
                                Gửi đi
                            </button>
                        </form>
                    @endif
                </div>
            @else
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #64748b;">
                    <x-heroicon-o-chat-bubble-left-right class="giant-icon-fix" />
                    <p style="font-weight: 500;">Chọn một đoạn chat bên trái để bắt đầu hỗ trợ</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>