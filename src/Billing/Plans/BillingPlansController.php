<?php namespace Vebto\Billing\Plans;

use Vebto\Billing\BillingPlan;
use Vebto\Billing\Gateways\Contracts\GatewayInterface;
use Vebto\Billing\Gateways\GatewayFactory;
use Vebto\Billing\Plans\Gateways\GatewayPlans;
use Vebto\Billing\Plans\Gateways\PaypalPlans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Vebto\Bootstrap\Controller;
use Vebto\Database\Paginator;

class BillingPlansController extends Controller
{
    /**
     * @var BillingPlan
     */
    private $plan;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var GatewayFactory
     */
    private $factory;

    /**
     * BillingPlansController constructor.
     *
     * @param BillingPlan $plan
     * @param Request $request
     * @param GatewayFactory $factory
     */
    public function __construct(BillingPlan $plan, Request $request, GatewayFactory $factory)
    {
        $this->plan = $plan;
        $this->request = $request;
        $this->factory = $factory;
    }

    /**
     * Display a listing of the resource.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function index()
    {
        $this->authorize('index', BillingPlan::class);

        return (new Paginator($this->plan))->with('parent')->paginate($this->request->all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return BillingPlan|JsonResponse
     * @throws \Exception
     */
    public function store()
    {
        $this->authorize('store', BillingPlan::class);

        $this->validate($this->request, [
            'name' => 'required|string|max:250|unique:billing_plans',
            'currency' => 'required_unless:free,1|string|max:255',
            'interval' => 'required_unless:free,1|string|max:255',
            'amount' => 'required_unless:free,1|integer|min:0',
            'permissions' => 'array',
            'show_permissions' => 'required|in:0,1',
            'recommended' => 'required|in:0,1',
            'position' => 'required|integer',
        ]);

        $data = $this->request->all();
        $data['uuid'] = str_random(36);

        $plan = $this->plan->create($data);

        $this->factory->getEnabledGateways()->each(function(GatewayInterface $gateway) use($plan) {
            $gateway->plans()->create($plan);
        });

        return $this->success(['plan' => $plan]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int $id
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        $this->authorize('update', BillingPlan::class);

        $this->validate($this->request, [
            'name' => 'required|string|max:250',
            'currency' => 'string|max:255',
            'interval' => 'string|max:255',
            'amount' => 'integer|min:0',
            'permissions' => 'array',
            'show_permissions' => 'boolean',
            'recommended' => 'boolean',
        ]);

        $plan = $this->plan->findOrFail($id)->fill($this->request->except('parent'));
        $plan->save();

        return $this->success(['plan' => $plan]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        $this->authorize('destroy', BillingPlan::class);

        $this->validate($this->request, [
            'ids' => 'required|array'
        ]);

        foreach ($this->request->get('ids') as $id) {
            $plan = $this->plan->find($id);
            if (is_null($plan)) continue;

            $plan->delete();

            $this->factory->getEnabledGateways()->each(function(GatewayInterface $gateway) use($plan) {
                $gateway->plans()->delete($plan);
            });
        }

        return $this->success();
    }

    /**
     * Sync billing plans across all enabled payment gateways.
     */
    public function sync()
    {
        ini_set('max_execution_time', 300);

        $plans = $this->plan->orderBy('parent_id', 'asc')->get();

        $this->factory->getEnabledGateways()->each(function($gateway) use($plans) {
            $plans->each(function(BillingPlan $plan) use($gateway) {
                if ( ! is_null($gateway->plans()->find($plan))) return;
                $gateway->plans()->create($plan);
            });
        });

        return $this->success();
    }
}
